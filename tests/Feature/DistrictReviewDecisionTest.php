<?php

use App\Models\DistrictReviewDecision;
use App\Models\Office;
use App\Models\OfficeReviewDecision;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\EligibleEmployee;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $view = Permission::findOrCreate('view district applications', 'web');
    $review = Permission::findOrCreate('review district applications', 'web');

    $role = Role::findOrCreate('District Approver', 'web');
    $role->syncPermissions([$view, $review]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function districtDecisionApprover(): User
{
    $user = User::factory()->create();

    $user->assignRole('District Approver');

    return $user;
}

function districtDecisionApplication(
    User $owner,
    User $officeReviewer
): array {
    $office = Office::create([
        'code' => 'DD-'.str()->upper(str()->random(8)),
        'name' => 'District Decision Test Office',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $cycle = TransferCycle::create([
        'name' => 'District Decision Test Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);

    $application = new TransferApplication();

    $application->transfer_cycle_id = $cycle->id;
    $application->reference_no = 'DD-'.str()->upper(str()->random(12));
    $application->user_id = $owner->id;
    $application->current_office_id = $office->id;
    $application->full_name = 'Test Applicant';
    $application->status = 'office_recommended';
    $application->submitted_at = now();

    $application->save();

    $officeDecision = OfficeReviewDecision::create([
        'transfer_application_id' => $application->id,
        'reviewer_id' => $officeReviewer->id,
        'office_id' => $office->id,
        'outcome' => 'forward',
        'recommendation' => 'not_recommended',
        'signed_paper_verified' => true,
        'remarks' => 'Office Head remarks.',
        'application_snapshot' => [],
        'decided_at' => now(),
    ]);

    return [$application, $officeDecision];
}

test('a district approver can view an office recommendation', function () {
    $approver = districtDecisionApprover();

    [$application] = districtDecisionApplication(
        User::factory()->create(),
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->get(route('district-reviews.show', $application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('district-reviews/show')
            ->where('application.id', $application->id)
            ->where('officeDecision.recommendation', 'not_recommended')
            ->where('officeDecision.remarks', 'Office Head remarks.')
        );
});

test('a district approver can approve an application', function () {
    $approver = districtDecisionApprover();

    [$application, $officeDecision] = districtDecisionApplication(
        User::factory()->create(),
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), [
            'office_review_decision_id' => $officeDecision->id,
            'outcome' => 'approved',
            'remarks' => null,
        ])
        ->assertRedirect(route('district-reviews.index'))
        ->assertSessionHasNoErrors();

    expect($application->fresh()->status)->toBe('approved');

    $this->assertDatabaseHas('district_review_decisions', [
        'transfer_application_id' => $application->id,
        'office_review_decision_id' => $officeDecision->id,
        'reviewer_id' => $approver->id,
        'outcome' => 'approved',
    ]);
});

test('rejection requires a reason', function () {
    $approver = districtDecisionApprover();

    [$application, $officeDecision] = districtDecisionApplication(
        User::factory()->create(),
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), [
            'office_review_decision_id' => $officeDecision->id,
            'outcome' => 'rejected',
            'remarks' => '',
        ])
        ->assertSessionHasErrors('remarks');

    expect($application->fresh()->status)->toBe('office_recommended');
});

test('a district approver can reject with a reason', function () {
    $approver = districtDecisionApprover();

    [$application, $officeDecision] = districtDecisionApplication(
        User::factory()->create(),
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), [
            'office_review_decision_id' => $officeDecision->id,
            'outcome' => 'rejected',
            'remarks' => 'Test rejection reason.',
        ])
        ->assertSessionHasNoErrors();

    expect($application->fresh()->status)->toBe('rejected');

    $this->assertDatabaseHas('district_review_decisions', [
        'transfer_application_id' => $application->id,
        'outcome' => 'rejected',
        'remarks' => 'Test rejection reason.',
    ]);
});

test('an officer cannot decide their own application', function () {
    $approver = districtDecisionApprover();

    [$application, $officeDecision] = districtDecisionApplication(
        $approver,
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), [
            'office_review_decision_id' => $officeDecision->id,
            'outcome' => 'approved',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('district_review_decisions', 0);
});

test('the office reviewer cannot make the district decision', function () {
    $approver = districtDecisionApprover();

    [$application, $officeDecision] = districtDecisionApplication(
        User::factory()->create(),
        $approver
    );

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), [
            'office_review_decision_id' => $officeDecision->id,
            'outcome' => 'approved',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('district_review_decisions', 0);
});

test('a second district decision cannot be recorded', function () {
    $approver = districtDecisionApprover();

    [$application, $officeDecision] = districtDecisionApplication(
        User::factory()->create(),
        User::factory()->create()
    );

    $data = [
        'office_review_decision_id' => $officeDecision->id,
        'outcome' => 'approved',
    ];

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), $data)
        ->assertSessionHasNoErrors();

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), $data)
        ->assertSessionHasErrors('outcome');

    expect(DistrictReviewDecision::count())->toBe(1);
});

test('a user without district review permission cannot make a decision', function () {
    $user = User::factory()->create();

    [$application, $officeDecision] = districtDecisionApplication(
        User::factory()->create(),
        User::factory()->create()
    );

    $this->actingAs($user)
        ->post(route('district-reviews.decision.store', $application), [
            'office_review_decision_id' => $officeDecision->id,
            'outcome' => 'approved',
        ])
        ->assertForbidden();
});

test('an employee can see the district rejection reason', function () {
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

    $approver = districtDecisionApprover();

    [$application, $officeDecision] = districtDecisionApplication(
        $employee,
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->post(route('district-reviews.decision.store', $application), [
            'office_review_decision_id' => $officeDecision->id,
            'outcome' => 'rejected',
            'remarks' => 'The application does not meet the stated requirements.',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($employee)
        ->get(route('transfer-applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transfer-applications/index')
            ->has('applications', 1)
            ->where('applications.0.id', $application->id)
            ->where('applications.0.status', 'rejected')
            ->where(
                'applications.0.district_rejection_reason',
                'The application does not meet the stated requirements.'
            )
        );
});
