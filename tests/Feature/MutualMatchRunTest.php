<?php

use App\Models\MutualMatchRun;
use App\Models\Service;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view mutual matches', 'web');
    Permission::findOrCreate('manage mutual matches', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function savedRunCycle(): TransferCycle
{
    return TransferCycle::create([
        'name' => 'Saved Run Test Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);
}

function savedRunService(): Service
{
    return Service::create([
        'code' => 'RUN-TEST',
        'name' => 'Saved Run Test Service',
        'is_active' => true,
    ]);
}

test('a user with manage permission can save a matching run', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage mutual matches');

    $cycle = savedRunCycle();
    $service = savedRunService();

    $this->actingAs($user)
        ->post(route('mutual-match-runs.store'), [
            'cycle_id' => $cycle->id,
            'service_id' => $service->id,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mutual_match_runs', [
        'transfer_cycle_id' => $cycle->id,
        'service_id' => $service->id,
        'created_by' => $user->id,
        'algorithm_version' => 'reciprocal-greedy-v1',
        'input_count' => 0,
        'candidate_count' => 0,
    ]);

    $run = MutualMatchRun::firstOrFail();

    expect($run->input_snapshot)->toBe([]);
    expect($run->candidate_snapshot)->toBe([]);
});

test('a user without manage permission cannot save a matching run', function () {
    $user = User::factory()->create();

    $cycle = savedRunCycle();
    $service = savedRunService();

    $this->actingAs($user)
        ->post(route('mutual-match-runs.store'), [
            'cycle_id' => $cycle->id,
            'service_id' => $service->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('mutual_match_runs', 0);
});

test('a user with view permission can see saved runs', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view mutual matches');

    $cycle = savedRunCycle();
    $service = savedRunService();

    $run = MutualMatchRun::create([
        'transfer_cycle_id' => $cycle->id,
        'service_id' => $service->id,
        'created_by' => $user->id,
        'algorithm_version' => 'reciprocal-greedy-v1',
        'input_count' => 0,
        'candidate_count' => 0,
        'input_snapshot' => [],
        'candidate_snapshot' => [],
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('mutual-match-runs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mutual-match-runs/index')
            ->has('runs.data', 1)
            ->where('runs.data.0.id', $run->id)
        );

    $this->actingAs($user)
        ->get(route('mutual-match-runs.show', $run))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('mutual-match-runs/show')
            ->where('run.id', $run->id)
            ->missing('run.input_snapshot')
        );
});

test('a user without view permission cannot view saved runs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mutual-match-runs.index'))
        ->assertForbidden();
});
