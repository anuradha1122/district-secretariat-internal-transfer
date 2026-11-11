<?php

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

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permission = Permission::findOrCreate(
        'view district applications',
        'web'
    );

    $role = Role::findOrCreate('District Approver', 'web');
    $role->givePermissionTo($permission);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function districtInboxApprover(): User
{
    $user = User::factory()->create();

    $user->assignRole('District Approver');

    return $user;
}

function districtInboxApplication(
    User $owner,
    string $recommendation,
    User $officeReviewer
): TransferApplication {
    $office = Office::create([
        'code' => 'DI-'.str()->upper(str()->random(8)),
        'name' => 'Test Office',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $cycle = TransferCycle::create([
        'name' => 'District Inbox Test Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);

    $application = new TransferApplication();

    $application->transfer_cycle_id = $cycle->id;
    $application->reference_no = 'DI-'.str()->upper(str()->random(12));
    $application->user_id = $owner->id;
    $application->current_office_id = $office->id;
    $application->full_name = 'Test Applicant';
    $application->status = 'office_recommended';
    $application->submitted_at = now();

    $application->save();

    OfficeReviewDecision::create([
        'transfer_application_id' => $application->id,
        'reviewer_id' => $officeReviewer->id,
        'office_id' => $office->id,
        'outcome' => 'forward',
        'recommendation' => $recommendation,
        'signed_paper_verified' => true,
        'application_snapshot' => [],
        'decided_at' => now(),
    ]);

    return $application;
}

test('district approver sees positive and negative office recommendations', function () {
    $approver = districtInboxApprover();

    $positive = districtInboxApplication(
        User::factory()->create(),
        'recommended',
        User::factory()->create()
    );

    $negative = districtInboxApplication(
        User::factory()->create(),
        'not_recommended',
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->get(route('district-reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('district-reviews/index')
            ->has('applications.data', 2)
            ->where('applications.data.0.id', $positive->id)
            ->where('applications.data.0.office_recommendation', 'recommended')
            ->where('applications.data.1.id', $negative->id)
            ->where('applications.data.1.office_recommendation', 'not_recommended')
        );
});

test('a district approver cannot see their own application', function () {
    $approver = districtInboxApprover();

    districtInboxApplication(
        $approver,
        'recommended',
        User::factory()->create()
    );

    $this->actingAs($approver)
        ->get(route('district-reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 0)
        );
});

test('an officer cannot perform both review stages on the same application', function () {
    $approver = districtInboxApprover();

    districtInboxApplication(
        User::factory()->create(),
        'recommended',
        $approver
    );

    $this->actingAs($approver)
        ->get(route('district-reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 0)
        );
});

test('users without the district approver role cannot open the inbox', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('district-reviews.index'))
        ->assertForbidden();
});
