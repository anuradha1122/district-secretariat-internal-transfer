<?php

use App\Models\EligibleEmployee;
use App\Models\Office;
use App\Models\Service;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use App\Services\Transfers\MutualTransferPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view mutual matches', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function mutualDbCycle(string $name): TransferCycle
{
    return TransferCycle::create([
        'name' => $name,
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);
}

function mutualDbService(string $code): Service
{
    return Service::create([
        'code' => $code,
        'name' => 'Test Service '.$code,
        'is_active' => true,
    ]);
}

function mutualDbOffice(string $code): Office
{
    return Office::create([
        'code' => $code,
        'name' => 'Test Office '.$code,
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);
}

function mutualDbEmployee(string $nic, bool $active = true): User
{
    $eligible = EligibleEmployee::create([
        'nic' => $nic,
        'is_active' => $active,
    ]);

    $user = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
    ]);

    $user->eligible_employee_id = $eligible->id;
    $user->save();

    return $user;
}

function mutualDbApplication(
    User $user,
    TransferCycle $cycle,
    Service $service,
    Office $currentOffice,
    string $reference,
    string $status = 'approved'
): TransferApplication {
    return TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $user->id,
        'reference_no' => $reference,
        'full_name' => 'Test Applicant '.$reference,
        'current_office_id' => $currentOffice->id,
        'service_id' => $service->id,
        'status' => $status,
        'submitted_at' => now(),
    ]);
}

function mutualDbPreference(
    TransferApplication $application,
    Office $office,
    int $priority
): void {
    $application->preferences()->create([
        'office_id' => $office->id,
        'priority' => $priority,
        'reason' => 'Test preference',
    ]);
}

test('the database preview finds approved reciprocal applications', function () {
    $cycle = mutualDbCycle('Test Cycle');
    $service = mutualDbService('TEST-DO');

    $officeA = mutualDbOffice('TEST-A');
    $officeB = mutualDbOffice('TEST-B');

    $userA = mutualDbEmployee('199012345678');
    $userB = mutualDbEmployee('199112345678');

    $applicationA = mutualDbApplication(
        $userA,
        $cycle,
        $service,
        $officeA,
        'MATCH-A'
    );

    $applicationB = mutualDbApplication(
        $userB,
        $cycle,
        $service,
        $officeB,
        'MATCH-B'
    );

    mutualDbPreference($applicationA, $officeB, 1);
    mutualDbPreference($applicationB, $officeA, 2);

    $results = app(MutualTransferPreviewService::class)
        ->preview($cycle->id, $service->id);

    expect($results)->toHaveCount(1);
    expect($results[0]['application_a']['id'])->toBe($applicationA->id);
    expect($results[0]['application_b']['id'])->toBe($applicationB->id);
    expect($results[0]['rank_a'])->toBe(1);
    expect($results[0]['rank_b'])->toBe(2);
    expect($results[0]['score'])->toBe(3);
    expect($results[0]['suggestion'])->toBe('proposed');
});

test('an inactive eligible employee is excluded from the preview', function () {
    $cycle = mutualDbCycle('Test Cycle');
    $service = mutualDbService('TEST-DO');

    $officeA = mutualDbOffice('TEST-A');
    $officeB = mutualDbOffice('TEST-B');

    $userA = mutualDbEmployee('199012345678');
    $userB = mutualDbEmployee('199112345678', false);

    $applicationA = mutualDbApplication(
        $userA,
        $cycle,
        $service,
        $officeA,
        'MATCH-A'
    );

    $applicationB = mutualDbApplication(
        $userB,
        $cycle,
        $service,
        $officeB,
        'MATCH-B'
    );

    mutualDbPreference($applicationA, $officeB, 1);
    mutualDbPreference($applicationB, $officeA, 1);

    $results = app(MutualTransferPreviewService::class)
        ->preview($cycle->id, $service->id);

    expect($results)->toHaveCount(0);
});

test('an unapproved application is excluded from the database preview', function () {
    $cycle = mutualDbCycle('Test Cycle');
    $service = mutualDbService('TEST-DO');

    $officeA = mutualDbOffice('TEST-A');
    $officeB = mutualDbOffice('TEST-B');

    $applicationA = mutualDbApplication(
        mutualDbEmployee('199012345678'),
        $cycle,
        $service,
        $officeA,
        'MATCH-A'
    );

    $applicationB = mutualDbApplication(
        mutualDbEmployee('199112345678'),
        $cycle,
        $service,
        $officeB,
        'MATCH-B',
        'submitted'
    );

    mutualDbPreference($applicationA, $officeB, 1);
    mutualDbPreference($applicationB, $officeA, 1);

    $results = app(MutualTransferPreviewService::class)
        ->preview($cycle->id, $service->id);

    expect($results)->toHaveCount(0);
});

test('applications from different cycles do not match', function () {
    $cycleA = mutualDbCycle('Cycle A');
    $cycleB = mutualDbCycle('Cycle B');

    $service = mutualDbService('TEST-DO');

    $officeA = mutualDbOffice('TEST-A');
    $officeB = mutualDbOffice('TEST-B');

    $applicationA = mutualDbApplication(
        mutualDbEmployee('199012345678'),
        $cycleA,
        $service,
        $officeA,
        'MATCH-A'
    );

    $applicationB = mutualDbApplication(
        mutualDbEmployee('199112345678'),
        $cycleB,
        $service,
        $officeB,
        'MATCH-B'
    );

    mutualDbPreference($applicationA, $officeB, 1);
    mutualDbPreference($applicationB, $officeA, 1);

    $results = app(MutualTransferPreviewService::class)
        ->preview($cycleA->id, $service->id);

    expect($results)->toHaveCount(0);
});

test('applications from different services do not match', function () {
    $cycle = mutualDbCycle('Test Cycle');

    $serviceA = mutualDbService('TEST-DO');
    $serviceB = mutualDbService('TEST-MSO');

    $officeA = mutualDbOffice('TEST-A');
    $officeB = mutualDbOffice('TEST-B');

    $applicationA = mutualDbApplication(
        mutualDbEmployee('199012345678'),
        $cycle,
        $serviceA,
        $officeA,
        'MATCH-A'
    );

    $applicationB = mutualDbApplication(
        mutualDbEmployee('199112345678'),
        $cycle,
        $serviceB,
        $officeB,
        'MATCH-B'
    );

    mutualDbPreference($applicationA, $officeB, 1);
    mutualDbPreference($applicationB, $officeA, 1);

    $results = app(MutualTransferPreviewService::class)
        ->preview($cycle->id, $serviceA->id);

    expect($results)->toHaveCount(0);
});

test('an authorized user can open the preview with cycle and service filters', function () {
    $cycle = mutualDbCycle('Test Cycle');
    $service = mutualDbService('TEST-DO');

    $officeA = mutualDbOffice('TEST-A');
    $officeB = mutualDbOffice('TEST-B');

    $applicationA = mutualDbApplication(
        mutualDbEmployee('199012345678'),
        $cycle,
        $service,
        $officeA,
        'MATCH-A'
    );

    $applicationB = mutualDbApplication(
        mutualDbEmployee('199112345678'),
        $cycle,
        $service,
        $officeB,
        'MATCH-B'
    );

    mutualDbPreference($applicationA, $officeB, 1);
    mutualDbPreference($applicationB, $officeA, 1);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo('view mutual matches');

    $this->actingAs($viewer)
        ->get(route('mutual-transfers.index', [
            'cycle_id' => $cycle->id,
            'service_id' => $service->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mutual-transfers/index')
            ->where('filters.cycle_id', $cycle->id)
            ->where('filters.service_id', $service->id)
            ->has('candidates', 1)
            ->where(
                'candidates.0.application_a.id',
                $applicationA->id
            )
            ->where(
                'candidates.0.application_b.id',
                $applicationB->id
            )
        );
});

test('a saved non-empty run preserves its inputs and candidate results', function () {
    $cycle = mutualDbCycle('Saved Snapshot Test Cycle');
    $service = mutualDbService('SNAP-DO');

    $officeA = mutualDbOffice('SNAP-A');
    $officeB = mutualDbOffice('SNAP-B');

    $userA = mutualDbEmployee('199012345678');
    $userB = mutualDbEmployee('199112345678');

    $applicationA = mutualDbApplication(
        $userA,
        $cycle,
        $service,
        $officeA,
        'SNAPSHOT-A'
    );

    $applicationB = mutualDbApplication(
        $userB,
        $cycle,
        $service,
        $officeB,
        'SNAPSHOT-B'
    );

    mutualDbPreference($applicationA, $officeB, 1);
    mutualDbPreference($applicationB, $officeA, 2);

    $managePermission = Permission::findOrCreate(
        'manage mutual matches',
        'web'
    );

    $manager = User::factory()->create();
    $manager->givePermissionTo($managePermission);

    $this->actingAs($manager)
        ->post(route('mutual-match-runs.store'), [
            'cycle_id' => $cycle->id,
            'service_id' => $service->id,
        ])
        ->assertSessionHasNoErrors();

    $run = \App\Models\MutualMatchRun::query()->firstOrFail();

    expect($run->input_count)->toBe(2);
    expect($run->candidate_count)->toBe(1);

    expect($run->input_snapshot)->toHaveCount(2);
    expect($run->candidate_snapshot)->toHaveCount(1);

    expect($run->input_snapshot[0]['application_id'])
        ->toBe($applicationA->id);

    expect($run->input_snapshot[0]['preferences'][0]['office_id'])
        ->toBe($officeB->id);

    expect($run->input_snapshot[0]['preferences'][0]['priority'])
        ->toBe(1);

    expect($run->candidate_snapshot[0]['application_a']['id'])
        ->toBe($applicationA->id);

    expect($run->candidate_snapshot[0]['application_b']['id'])
        ->toBe($applicationB->id);

    expect($run->candidate_snapshot[0]['rank_a'])->toBe(1);
    expect($run->candidate_snapshot[0]['rank_b'])->toBe(2);
    expect($run->candidate_snapshot[0]['score'])->toBe(3);

    /*
     * Simulate a later change. This changes the live preview,
     * but must not rewrite the saved run.
     */
    $applicationB->status = 'rejected';
    $applicationB->save();

    $liveResults = app(MutualTransferPreviewService::class)
        ->preview($cycle->id, $service->id);

    expect($liveResults)->toHaveCount(0);

    $run->refresh();

    expect($run->input_count)->toBe(2);
    expect($run->candidate_count)->toBe(1);
    expect($run->candidate_snapshot)->toHaveCount(1);

    expect($run->candidate_snapshot[0]['application_b']['id'])
        ->toBe($applicationB->id);

    expect($run->input_snapshot[1]['status'])->toBe('approved');
});
