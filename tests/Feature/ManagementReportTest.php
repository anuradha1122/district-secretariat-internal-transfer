<?php

use App\Models\Office;
use App\Models\Service;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view district reports', 'web');
    Permission::findOrCreate('export reports', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function reportViewer(bool $canExport = false): User
{
    $user = User::factory()->create();

    $user->givePermissionTo('view district reports');

    if ($canExport) {
        $user->givePermissionTo('export reports');
    }

    return $user;
}

function reportApplication(string $status = 'submitted'): TransferApplication
{
    $cycle = TransferCycle::create([
        'name' => 'Report Test Cycle',
        'opens_on' => now()->subDay()->toDateString(),
        'closes_on' => now()->addDay()->toDateString(),
        'status' => 'open',
    ]);

    $service = Service::create([
        'code' => 'REPORT-DO',
        'name' => 'Development Officer',
        'is_active' => true,
    ]);

    $office = Office::create([
        'code' => 'REPORT-A',
        'name' => 'Test Office A',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $user = User::factory()->create();

    return TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $user->id,
        'reference_no' => 'REPORT-001',
        'full_name' => '=TEST FORMULA',
        'current_office_id' => $office->id,
        'service_id' => $service->id,
        'status' => $status,
        'submitted_at' => $status === 'draft' ? null : now(),
    ]);
}

test('users without district report permission cannot view reports', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('reports.index'))
        ->assertForbidden();
});

test('a report viewer can view submitted applications', function () {
    $viewer = reportViewer();

    $application = reportApplication();

    $this->actingAs($viewer)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('report', 'applications')
            ->has('rows.data', 1)
            ->where('rows.data.0.application_id', $application->id)
        );
});

test('draft applications are excluded', function () {
    $viewer = reportViewer();

    reportApplication('draft');

    $this->actingAs($viewer)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 0)
        );
});

test('export requires both view and export permissions', function () {
    $viewer = reportViewer();

    $this->actingAs($viewer)
        ->get(route('reports.csv'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(route('reports.pdf'))
        ->assertForbidden();
});

test('csv export escapes formula-like values', function () {
    $viewer = reportViewer(true);

    reportApplication();

    $response = $this->actingAs($viewer)
        ->get(route('reports.csv'));

    $response->assertOk();

    expect($response->streamedContent())
        ->toContain("'=TEST FORMULA");
});

test('preferences report contains one row per preference', function () {
    $viewer = reportViewer();

    $application = reportApplication();

    $destination = Office::create([
        'code' => 'REPORT-B',
        'name' => 'Test Office B',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $application->preferences()->create([
        'office_id' => $destination->id,
        'priority' => 1,
        'reason' => 'Test',
    ]);

    $this->actingAs($viewer)
        ->get(route('reports.index', [
            'report' => 'preferences',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('report', 'preferences')
            ->has('rows.data', 1)
            ->where('rows.data.0.destination_office_name', 'Test Office B')
            ->where('rows.data.0.priority', 1)
        );
});