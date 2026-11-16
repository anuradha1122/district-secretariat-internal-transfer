<?php

use App\Models\EligibleEmployee;
use App\Models\MutualMatchDecision;
use App\Models\MutualMatchRun;
use App\Models\Office;
use App\Models\Service;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view mutual matches', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('users without permission cannot view reconciliation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mutual-match-reconciliation.index'))
        ->assertForbidden();
});

test('an inactive confirmed participant appears for review', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('view mutual matches');

    $reviewer = User::factory()->create();

    $eligibleA = EligibleEmployee::create([
        'nic' => '199012345678',
        'is_active' => false,
    ]);

    $eligibleB = EligibleEmployee::create([
        'nic' => '199112345678',
        'is_active' => true,
    ]);

    $userA = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
    ]);

    $userA->eligible_employee_id = $eligibleA->id;
    $userA->save();

    $userB = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
    ]);

    $userB->eligible_employee_id = $eligibleB->id;
    $userB->save();

    $cycle = TransferCycle::create([
        'name' => 'Reconciliation Test Cycle',
        'opens_on' => now()->subDay()->toDateString(),
        'closes_on' => now()->addDay()->toDateString(),
        'status' => 'open',
    ]);

    $service = Service::create([
        'code' => 'RECON-SVC',
        'name' => 'Reconciliation Service',
        'is_active' => true,
    ]);

    $office = Office::create([
        'code' => 'RECON-OFF',
        'name' => 'Reconciliation Office',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $applicationA = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $userA->id,
        'reference_no' => 'RECON-A',
        'full_name' => 'Test Applicant A',
        'current_office_id' => $office->id,
        'service_id' => $service->id,
        'status' => 'approved',
        'submitted_at' => now(),
    ]);

    $applicationB = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $userB->id,
        'reference_no' => 'RECON-B',
        'full_name' => 'Test Applicant B',
        'current_office_id' => $office->id,
        'service_id' => $service->id,
        'status' => 'approved',
        'submitted_at' => now(),
    ]);

    $run = MutualMatchRun::create([
        'transfer_cycle_id' => $cycle->id,
        'service_id' => $service->id,
        'created_by' => $reviewer->id,
        'algorithm_version' => 'reciprocal-greedy-v1',
        'input_count' => 2,
        'candidate_count' => 1,
        'input_snapshot' => [],
        'candidate_snapshot' => [],
        'generated_at' => now(),
    ]);

    $decision = MutualMatchDecision::create([
        'mutual_match_run_id' => $run->id,
        'transfer_cycle_id' => $cycle->id,
        'application_a_id' => $applicationA->id,
        'application_b_id' => $applicationB->id,
        'reviewer_id' => $reviewer->id,
        'outcome' => 'confirmed',
        'decided_at' => now(),
    ]);

    foreach ([
        [$userA, $applicationA],
        [$userB, $applicationB],
    ] as [$user, $application]) {
        DB::table('mutual_match_participants')->insert([
            'mutual_match_decision_id' => $decision->id,
            'transfer_cycle_id' => $cycle->id,
            'user_id' => $user->id,
            'transfer_application_id' => $application->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->actingAs($viewer)
        ->get(route('mutual-match-reconciliation.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mutual-match-reconciliation/index')
            ->has('items.data', 1)
            ->where('items.data.0.decision_id', $decision->id)
            ->where('items.data.0.user_id', $userA->id)
            ->where(
                'items.data.0.issue',
                'Employee eligibility is inactive'
            )
        );

    $this->assertDatabaseHas('mutual_match_decisions', [
        'id' => $decision->id,
        'outcome' => 'confirmed',
    ]);
});