<?php

use App\Models\Office;
use App\Models\TransferApplication;
use App\Models\TransferApplicationPreference;
use App\Models\User;
use App\Services\Transfers\MutualTransferPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function previewTestApplication(
    int $id,
    int $userId,
    int $officeId,
    int $cycleId = 1,
    int $serviceId = 1,
    string $status = 'approved'
): TransferApplication {
    $application = new TransferApplication();

    $application->id = $id;
    $application->user_id = $userId;
    $application->current_office_id = $officeId;
    $application->transfer_cycle_id = $cycleId;
    $application->service_id = $serviceId;
    $application->status = $status;
    $application->full_name = 'Applicant '.$id;
    $application->reference_no = 'TEST-'.$id;
    $application->submitted_at = now();

    $office = new Office();
    $office->id = $officeId;
    $office->code = 'OFF-'.$officeId;
    $office->name = 'Office '.$officeId;

    $application->setRelation('currentOffice', $office);
    $application->setRelation('preferences', collect());

    return $application;
}

function previewTestPreference(int $officeId, int $priority): TransferApplicationPreference
{
    $preference = new TransferApplicationPreference();

    $preference->office_id = $officeId;
    $preference->priority = $priority;

    return $preference;
}

test('reciprocal preferences create one candidate pair', function () {
    $a = previewTestApplication(1, 1, 10);
    $b = previewTestApplication(2, 2, 20);

    $a->setRelation('preferences', collect([
        previewTestPreference(20, 1),
    ]));

    $b->setRelation('preferences', collect([
        previewTestPreference(10, 2),
    ]));

    $results = app(MutualTransferPreviewService::class)
        ->buildCandidates(collect([$a, $b]));

    expect($results)->toHaveCount(1);
    expect($results[0]['score'])->toBe(3);
    expect($results[0]['suggestion'])->toBe('proposed');
});

test('a one-way preference does not create a match', function () {
    $a = previewTestApplication(1, 1, 10);
    $b = previewTestApplication(2, 2, 20);

    $a->setRelation('preferences', collect([
        previewTestPreference(20, 1),
    ]));

    $results = app(MutualTransferPreviewService::class)
        ->buildCandidates(collect([$a, $b]));

    expect($results)->toHaveCount(0);
});

test('applications from different services do not match', function () {
    $a = previewTestApplication(1, 1, 10, 1, 1);
    $b = previewTestApplication(2, 2, 20, 1, 2);

    $a->setRelation('preferences', collect([
        previewTestPreference(20, 1),
    ]));

    $b->setRelation('preferences', collect([
        previewTestPreference(10, 1),
    ]));

    $results = app(MutualTransferPreviewService::class)
        ->buildCandidates(collect([$a, $b]));

    expect($results)->toHaveCount(0);
});

test('unapproved applications do not match', function () {
    $a = previewTestApplication(1, 1, 10);
    $b = previewTestApplication(2, 2, 20, 1, 1, 'submitted');

    $a->setRelation('preferences', collect([
        previewTestPreference(20, 1),
    ]));

    $b->setRelation('preferences', collect([
        previewTestPreference(10, 1),
    ]));

    $results = app(MutualTransferPreviewService::class)
        ->buildCandidates(collect([$a, $b]));

    expect($results)->toHaveCount(0);
});

test('competing pairs retain a conflict alternative', function () {
    $a = previewTestApplication(1, 1, 10);
    $b = previewTestApplication(2, 2, 20);
    $c = previewTestApplication(3, 3, 30);

    $a->setRelation('preferences', collect([
        previewTestPreference(20, 1),
        previewTestPreference(30, 2),
    ]));

    $b->setRelation('preferences', collect([
        previewTestPreference(10, 1),
    ]));

    $c->setRelation('preferences', collect([
        previewTestPreference(10, 2),
    ]));

    $results = app(MutualTransferPreviewService::class)
        ->buildCandidates(collect([$a, $b, $c]));

    expect($results)->toHaveCount(2);
    expect($results[0]['score'])->toBe(2);
    expect($results[0]['suggestion'])->toBe('proposed');
    expect($results[1]['suggestion'])->toBe('conflict');
});

test('users without mutual match permission cannot open the preview', function () {
    Permission::findOrCreate('view mutual matches', 'web');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mutual-transfers.index'))
        ->assertForbidden();
});
