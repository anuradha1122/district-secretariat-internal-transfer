<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view services', 'web');
    Permission::findOrCreate('manage services', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('an authorized user can create and update a service', function () {
    $user = User::factory()->create();

    $user->givePermissionTo([
        'view services',
        'manage services',
    ]);

    $this->actingAs($user)
        ->post(route('services.store'), [
            'code' => 'do',
            'name' => 'Development Officer',
            'is_active' => true,
        ])
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseHas('services', [
        'code' => 'DO',
        'name' => 'Development Officer',
    ]);

    $service = Service::where('code', 'DO')->firstOrFail();

    $this->actingAs($user)
        ->put(route('services.update', $service), [
            'code' => 'DO',
            'name' => 'Development Officer Service',
            'is_active' => false,
        ])
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => 'Development Officer Service',
        'is_active' => false,
    ]);
});

test('a duplicate service code is rejected', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage services');

    Service::create([
        'code' => 'MSO',
        'name' => 'Management Service Officer',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->from(route('services.index'))
        ->post(route('services.store'), [
            'code' => 'mso',
            'name' => 'Another service',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('code');

    expect(Service::count())->toBe(1);
});

test('a user without permission cannot manage services', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('services.store'), [
            'code' => 'DO',
            'name' => 'Development Officer',
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('services', [
        'code' => 'DO',
    ]);
});

test('a user without permission cannot view services', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('services.index'))
        ->assertForbidden();
});
