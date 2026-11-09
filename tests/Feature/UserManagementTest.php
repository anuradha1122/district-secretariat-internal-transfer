<?php

use App\Models\Office;
use App\Models\OfficeReviewerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $viewUsers = Permission::findOrCreate('view users', 'web');
    $manageUsers = Permission::findOrCreate('manage users', 'web');

    $superAdmin = Role::findOrCreate('Super Admin', 'web');
    $superAdmin->syncPermissions([$viewUsers, $manageUsers]);

    $admin = Role::findOrCreate('Admin', 'web');
    $admin->syncPermissions([$viewUsers, $manageUsers]);

    Role::findOrCreate('Data Entry Officer', 'web');
    Role::findOrCreate('Office Head', 'web');
    Role::findOrCreate('District Approver', 'web');
    Role::findOrCreate('Auditor', 'web');
    Role::findOrCreate('Employee', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function userManagementSuperAdmin(): User
{
    $user = User::factory()->create();

    $user->assignRole('Super Admin');

    return $user;
}

test('a super admin can create a staff account', function () {
    $admin = userManagementSuperAdmin();

    $this->actingAs($admin)
        ->post(route('user-management.store'), [
            'name' => 'Test Office Head',
            'email' => 'office.head@example.test',
            'password' => 'Testing123456',
            'password_confirmation' => 'Testing123456',
            'roles' => ['Office Head'],
            'email_verified' => true,
        ])
        ->assertRedirect(route('user-management.index'))
        ->assertSessionHasNoErrors();

    $user = User::where('email', 'office.head@example.test')->firstOrFail();

    expect($user->hasRole('Office Head'))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('Testing123456', $user->password))->toBeTrue();
});

test('email verification confirmation is required', function () {
    $admin = userManagementSuperAdmin();

    $this->actingAs($admin)
        ->post(route('user-management.store'), [
            'name' => 'Test Office Head',
            'email' => 'office.head@example.test',
            'password' => 'Testing123456',
            'password_confirmation' => 'Testing123456',
            'roles' => ['Office Head'],
            'email_verified' => false,
        ])
        ->assertSessionHasErrors('email_verified');

    $this->assertDatabaseMissing('users', [
        'email' => 'office.head@example.test',
    ]);
});

test('a normal admin cannot create users or assign roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('user-management.store'), [])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('user-management.roles.update', $target), [
            'roles' => ['Admin'],
        ])
        ->assertForbidden();
});

test('staff role changes preserve the employee role', function () {
    $admin = userManagementSuperAdmin();

    $employee = User::factory()->create();
    $employee->assignRole('Employee');

    $this->actingAs($admin)
        ->put(route('user-management.roles.update', $employee), [
            'roles' => ['Office Head'],
        ])
        ->assertRedirect(route('user-management.index'))
        ->assertSessionHasNoErrors();

    $employee->refresh();

    expect($employee->hasRole('Employee'))->toBeTrue();
    expect($employee->hasRole('Office Head'))->toBeTrue();
});

test('super admin and own roles cannot be changed through this page', function () {
    $admin = userManagementSuperAdmin();

    $anotherSuperAdmin = userManagementSuperAdmin();

    $this->actingAs($admin)
        ->put(route('user-management.roles.update', $admin), [
            'roles' => ['Admin'],
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('user-management.roles.update', $anotherSuperAdmin), [
            'roles' => ['Admin'],
        ])
        ->assertForbidden();

    expect($admin->fresh()->hasRole('Super Admin'))->toBeTrue();
    expect($anotherSuperAdmin->fresh()->hasRole('Super Admin'))->toBeTrue();
});

test('super admin role cannot be assigned through staff role input', function () {
    $admin = userManagementSuperAdmin();

    $target = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('user-management.roles.update', $target), [
            'roles' => ['Super Admin'],
        ])
        ->assertSessionHasErrors();

    expect($target->fresh()->hasRole('Super Admin'))->toBeFalse();
});

test('removing office head role revokes active office assignments', function () {
    $admin = userManagementSuperAdmin();

    $reviewer = User::factory()->create();
    $reviewer->assignRole('Office Head');

    $office = Office::create([
        'code' => 'TEST-UM-01',
        'name' => 'Test Office',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $assignment = OfficeReviewerAssignment::create([
        'user_id' => $reviewer->id,
        'office_id' => $office->id,
        'is_active' => true,
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($admin)
        ->put(route('user-management.roles.update', $reviewer), [
            'roles' => [],
        ])
        ->assertRedirect(route('user-management.index'))
        ->assertSessionHasNoErrors();

    expect($reviewer->fresh()->hasRole('Office Head'))->toBeFalse();

    $this->assertDatabaseHas('office_reviewer_assignments', [
        'id' => $assignment->id,
        'is_active' => false,
        'revoked_by' => $admin->id,
    ]);
});

test('a user without view users permission cannot view user management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user-management.index'))
        ->assertForbidden();
});
