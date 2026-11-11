<?php

use App\Models\Office;
use App\Models\OfficeReviewDecision;
use App\Models\OfficeReviewerAssignment;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\EligibleEmployee;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permission = Permission::findOrCreate(
        'review office applications',
        'web'
    );

    $role = Role::findOrCreate('Office Head', 'web');
    $role->givePermissionTo($permission);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function decisionReviewer(): User
{
    $user = User::factory()->create();

    $user->assignRole('Office Head');

    return $user;
}

function decisionOffice(string $code): Office
{
    return Office::create([
        'code' => $code,
        'name' => 'Test Office '.$code,
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);
}

function decisionAssignment(User $reviewer, Office $office): void
{
    OfficeReviewerAssignment::create([
        'user_id' => $reviewer->id,
        'office_id' => $office->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);
}

function decisionApplication(User $owner, Office $office): TransferApplication
{
    $cycle = TransferCycle::create([
        'name' => 'Decision Test Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);

    $application = new TransferApplication();

    $application->transfer_cycle_id = $cycle->id;
    $application->reference_no = 'TEST-'.str()->upper(str()->random(12));
    $application->user_id = $owner->id;
    $application->current_office_id = $office->id;
    $application->full_name = 'Test Applicant';
    $application->status = 'submitted';
    $application->declaration_accepted = true;
    $application->submitted_at = now();

    $application->save();

    return $application;
}

function decisionForwardData(): array
{
    return [
        'outcome' => 'forward',
        'recommendation' => 'recommended',
        'insufficient_service' => true,
        'replacement_required' => false,
        'pending_matters' => false,
        'other_circumstances' => false,
        'signed_paper_verified' => true,
        'remarks' => null,
    ];
}

test('an assigned office head can forward a recommendation', function () {
    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication(
        User::factory()->create(),
        $office
    );

    $this->actingAs($reviewer)
        ->post(
            route('office-reviews.decision.store', $application),
            decisionForwardData()
        )
        ->assertRedirect(route('office-reviews.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('transfer_applications', [
        'id' => $application->id,
        'status' => 'office_recommended',
    ]);

    $this->assertDatabaseHas('office_review_decisions', [
        'transfer_application_id' => $application->id,
        'reviewer_id' => $reviewer->id,
        'outcome' => 'forward',
        'recommendation' => 'recommended',
        'insufficient_service' => true,
        'signed_paper_verified' => true,
    ]);

    $decision = OfficeReviewDecision::firstOrFail();

    expect($decision->application_snapshot['application']['full_name'])
        ->toBe('Test Applicant');
});

test('a recommendation can be forwarded as not recommended', function () {
    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication(
        User::factory()->create(),
        $office
    );

    $data = decisionForwardData();
    $data['recommendation'] = 'not_recommended';

    $this->actingAs($reviewer)
        ->post(route('office-reviews.decision.store', $application), $data)
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('office_review_decisions', [
        'transfer_application_id' => $application->id,
        'outcome' => 'forward',
        'recommendation' => 'not_recommended',
    ]);
});

test('signed paper confirmation is required before forwarding', function () {
    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication(
        User::factory()->create(),
        $office
    );

    $data = decisionForwardData();
    $data['signed_paper_verified'] = false;

    $this->actingAs($reviewer)
        ->post(route('office-reviews.decision.store', $application), $data)
        ->assertSessionHasErrors('signed_paper_verified');

    expect($application->fresh()->status)->toBe('submitted');

    $this->assertDatabaseCount('office_review_decisions', 0);
});

test('returning requires a reason and reopens the draft', function () {
    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication(
        User::factory()->create(),
        $office
    );

    $this->actingAs($reviewer)
        ->post(route('office-reviews.decision.store', $application), [
            'outcome' => 'returned',
            'remarks' => 'Please correct your current workplace details.',
        ])
        ->assertSessionHasNoErrors();

    $application->refresh();

    expect($application->status)->toBe('draft');
    expect($application->declaration_accepted)->toBeFalse();
    expect($application->submitted_at)->toBeNull();

    $this->assertDatabaseHas('office_review_decisions', [
        'transfer_application_id' => $application->id,
        'outcome' => 'returned',
        'remarks' => 'Please correct your current workplace details.',
    ]);
});

test('a return without a reason is rejected', function () {
    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication(
        User::factory()->create(),
        $office
    );

    $this->actingAs($reviewer)
        ->post(route('office-reviews.decision.store', $application), [
            'outcome' => 'returned',
            'remarks' => '',
        ])
        ->assertSessionHasErrors('remarks');

    expect($application->fresh()->status)->toBe('submitted');
});

test('an office head cannot review another office application', function () {
    $reviewer = decisionReviewer();

    $assignedOffice = decisionOffice('DEC-A');
    $otherOffice = decisionOffice('DEC-B');

    decisionAssignment($reviewer, $assignedOffice);

    $application = decisionApplication(
        User::factory()->create(),
        $otherOffice
    );

    $this->actingAs($reviewer)
        ->post(
            route('office-reviews.decision.store', $application),
            decisionForwardData()
        )
        ->assertForbidden();

    $this->assertDatabaseCount('office_review_decisions', 0);
});

test('an office head cannot review their own application', function () {
    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication($reviewer, $office);

    $this->actingAs($reviewer)
        ->post(
            route('office-reviews.decision.store', $application),
            decisionForwardData()
        )
        ->assertForbidden();

    $this->assertDatabaseCount('office_review_decisions', 0);
});

test('a second decision cannot be made on the same submission', function () {
    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication(
        User::factory()->create(),
        $office
    );

    $this->actingAs($reviewer)
        ->post(
            route('office-reviews.decision.store', $application),
            decisionForwardData()
        )
        ->assertSessionHasNoErrors();

    $this->actingAs($reviewer)
        ->post(
            route('office-reviews.decision.store', $application),
            decisionForwardData()
        )
        ->assertSessionHasErrors('outcome');

    $this->assertDatabaseCount('office_review_decisions', 1);
});


test('an employee can see the reason their application was returned', function () {
    $employeeRole = Role::findOrCreate('Employee', 'web');

    $viewOwnPermission = Permission::findOrCreate(
        'view own applications',
        'web'
    );

    $employeeRole->givePermissionTo($viewOwnPermission);

    $eligible = EligibleEmployee::create([
        'nic' => '199012345678',
        'is_active' => true,
    ]);

    $employee = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
    ]);

    $employee->eligible_employee_id = $eligible->id;
    $employee->save();
    $employee->assignRole('Employee');

    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication($employee, $office);

    $this->actingAs($reviewer)
        ->post(route('office-reviews.decision.store', $application), [
            'outcome' => 'returned',
            'remarks' => 'Please correct your current workplace details.',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($employee)
        ->get(route('transfer-applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transfer-applications/index')
            ->has('applications', 1)
            ->where('applications.0.id', $application->id)
            ->where('applications.0.status', 'draft')
            ->where('applications.0.latest_office_outcome', 'returned')
            ->where(
                'applications.0.latest_office_remarks',
                'Please correct your current workplace details.'
            )
        );
});


test('office recommendation remarks are not sent to the employee', function () {
    $employeeRole = Role::findOrCreate('Employee', 'web');

    $viewOwnPermission = Permission::findOrCreate(
        'view own applications',
        'web'
    );

    $employeeRole->givePermissionTo($viewOwnPermission);

    $eligible = EligibleEmployee::create([
        'nic' => '199012345678',
        'is_active' => true,
    ]);

    $employee = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
    ]);

    $employee->eligible_employee_id = $eligible->id;
    $employee->save();
    $employee->assignRole('Employee');

    $reviewer = decisionReviewer();
    $office = decisionOffice('DEC-A');

    decisionAssignment($reviewer, $office);

    $application = decisionApplication($employee, $office);

    $data = decisionForwardData();
    $data['remarks'] = 'Confidential office recommendation notes.';

    $this->actingAs($reviewer)
        ->post(route('office-reviews.decision.store', $application), $data)
        ->assertSessionHasNoErrors();

    $this->actingAs($employee)
        ->get(route('transfer-applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transfer-applications/index')
            ->has('applications', 1)
            ->where('applications.0.id', $application->id)
            ->where('applications.0.status', 'office_recommended')
            ->where('applications.0.latest_office_outcome', 'forward')
            ->where('applications.0.latest_office_remarks', null)
        )
        ->assertDontSee('Confidential office recommendation notes.');
});
