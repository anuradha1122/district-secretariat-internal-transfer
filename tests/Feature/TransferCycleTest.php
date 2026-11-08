<?php

use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view transfer cycles', 'web');
    Permission::findOrCreate('manage transfer cycles', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('an authorized user can create a transfer cycle', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('manage transfer cycles');

    $this->actingAs($user)
        ->post(route('transfer-cycles.store'), [
            'name' => 'Annual Transfers 2027',
            'opens_on' => '2027-01-01',
            'closes_on' => '2027-01-31',
            'status' => 'draft',
            'description' => 'Annual transfer applications.',
        ])
        ->assertRedirect(route('transfer-cycles.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('transfer_cycles', [
        'name' => 'Annual Transfers 2027',
        'status' => 'draft',
        'created_by' => $user->id,
    ]);
});

test('a closing date before the opening date is rejected', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('manage transfer cycles');

    $this->actingAs($user)
        ->post(route('transfer-cycles.store'), [
            'name' => 'Invalid Cycle',
            'opens_on' => '2027-02-01',
            'closes_on' => '2027-01-01',
            'status' => 'draft',
        ])
        ->assertSessionHasErrors('closes_on');

    expect(TransferCycle::count())->toBe(0);
});

test('a user without permission cannot create a cycle', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('transfer-cycles.store'), [
            'name' => 'Annual Transfers 2027',
            'opens_on' => '2027-01-01',
            'closes_on' => '2027-01-31',
            'status' => 'draft',
        ])
        ->assertForbidden();
});
