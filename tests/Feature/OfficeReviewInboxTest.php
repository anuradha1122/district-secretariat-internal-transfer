<?php

use App\Models\Office;
use App\Models\OfficeReviewerAssignment;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permission = Permission::findOrCreate(
        'view office applications',
        'web'
    );

    $role = Role::findOrCreate('Office Head', 'web');
    $role->givePermissionTo($permission);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function inboxReviewer(): User
{
    $user = User::factory()->create();

    $user->assignRole('Office Head');

    return $user;
}

function inboxOffice(string $code, bool $active = true): Office
{
    return Office::create([
        'code' => $code,
        'name' => 'Office '.$code,
        'type' => 'district_secretariat',
        'is_active' => $active,
    ]);
}

function inboxAssignment(User $reviewer, Office $office, bool $active = true): void
{
    OfficeReviewerAssignment::create([
        'user_id' => $reviewer->id,
        'office_id' => $office->id,
        'is_active' => $active,
        'assigned_at' => now(),
    ]);
}

function inboxCycle(): TransferCycle
{
    return TransferCycle::create([
        'name' => 'Inbox Test Cycle',
        'opens_on' => now()->subDay()->toDateString(),
        'closes_on' => now()->addDay()->toDateString(),
        'status' => 'open',
    ]);
}

function inboxApplication(
    User $user,
    Office $office,
    TransferCycle $cycle,
    string $status = 'submitted'
): TransferApplication {
    $application = new TransferApplication();

    $application->transfer_cycle_id = $cycle->id;
    $application->reference_no = 'TEST-'.str()->upper(str()->random(12));
    $application->user_id = $user->id;
    $application->current_office_id = $office->id;
    $application->full_name = 'Test Applicant';
    $application->status = $status;
    $application->submitted_at = $status === 'submitted' ? now() : null;

    $application->save();

    return $application;
}

test('an office head sees only submitted applications in assigned offices', function () {
    $reviewer = inboxReviewer();

    $assignedOffice = inboxOffice('TEST-A');
    $otherOffice = inboxOffice('TEST-B');

    inboxAssignment($reviewer, $assignedOffice);

    $cycle = inboxCycle();

    $visible = inboxApplication(
        User::factory()->create(),
        $assignedOffice,
        $cycle
    );

    inboxApplication(
        User::factory()->create(),
        $otherOffice,
        $cycle
    );

    inboxApplication(
        User::factory()->create(),
        $assignedOffice,
        $cycle,
        'draft'
    );

    $this->actingAs($reviewer)
        ->get(route('office-reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('office-reviews/index')
            ->has('applications.data', 1)
            ->where('applications.data.0.id', $visible->id)
        );
});

test('an office head cannot open an application from another office', function () {
    $reviewer = inboxReviewer();

    $assignedOffice = inboxOffice('TEST-A');
    $otherOffice = inboxOffice('TEST-B');

    inboxAssignment($reviewer, $assignedOffice);

    $application = inboxApplication(
        User::factory()->create(),
        $otherOffice,
        inboxCycle()
    );

    $this->actingAs($reviewer)
        ->get(route('office-reviews.show', $application))
        ->assertNotFound();
});

test('an office head can open a submitted application in an assigned office', function () {
    $reviewer = inboxReviewer();
    $office = inboxOffice('TEST-A');

    inboxAssignment($reviewer, $office);

    $application = inboxApplication(
        User::factory()->create(),
        $office,
        inboxCycle()
    );

    $this->actingAs($reviewer)
        ->get(route('office-reviews.show', $application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('office-reviews/show')
            ->where('application.id', $application->id)
        );
});

test('an office head cannot view their own application in the review inbox', function () {
    $reviewer = inboxReviewer();
    $office = inboxOffice('TEST-A');

    inboxAssignment($reviewer, $office);

    $application = inboxApplication(
        $reviewer,
        $office,
        inboxCycle()
    );

    $this->actingAs($reviewer)
        ->get(route('office-reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 0)
        );

    $this->actingAs($reviewer)
        ->get(route('office-reviews.show', $application))
        ->assertNotFound();
});

test('revoked assignments do not grant access', function () {
    $reviewer = inboxReviewer();
    $office = inboxOffice('TEST-A');

    inboxAssignment($reviewer, $office, false);

    $application = inboxApplication(
        User::factory()->create(),
        $office,
        inboxCycle()
    );

    $this->actingAs($reviewer)
        ->get(route('office-reviews.show', $application))
        ->assertNotFound();
});

test('inactive offices do not grant access', function () {
    $reviewer = inboxReviewer();
    $office = inboxOffice('TEST-A', false);

    inboxAssignment($reviewer, $office);

    $application = inboxApplication(
        User::factory()->create(),
        $office,
        inboxCycle()
    );

    $this->actingAs($reviewer)
        ->get(route('office-reviews.show', $application))
        ->assertNotFound();
});

test('users without the office head role cannot open the inbox', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('office-reviews.index'))
        ->assertForbidden();
});
