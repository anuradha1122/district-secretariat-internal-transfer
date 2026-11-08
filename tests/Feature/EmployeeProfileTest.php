<?php

use App\Models\EligibleEmployee;
use App\Models\EmployeeProfile;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('Employee', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function profileEmployee(): User
{
    $eligible = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $user = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
    ]);

    $user->eligible_employee_id = $eligible->id;
    $user->save();
    $user->assignRole('Employee');

    return $user;
}

test('an employee can create their own profile', function () {
    $user = profileEmployee();

    $office = Office::create([
        'code' => 'DS-01',
        'name' => 'District Secretariat',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $service = Service::create([
        'code' => 'DO',
        'name' => 'Development Officer',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('employee-profile.update'), [
            'name_with_initials' => 'A. B. Perera',
            'designation' => 'Development Officer',
            'employee_no' => 'EMP-001',
            'mobile_no' => '0771234567',
            'office_id' => $office->id,
            'service_id' => $service->id,
        ])
        ->assertRedirect(route('employee-profile.edit'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('employee_profiles', [
        'user_id' => $user->id,
        'office_id' => $office->id,
        'service_id' => $service->id,
    ]);
});

test('an inactive office cannot be selected', function () {
    $user = profileEmployee();

    $office = Office::create([
        'code' => 'DS-01',
        'name' => 'District Secretariat',
        'type' => 'district_secretariat',
        'is_active' => false,
    ]);

    $service = Service::create([
        'code' => 'DO',
        'name' => 'Development Officer',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('employee-profile.update'), [
            'name_with_initials' => 'A. B. Perera',
            'designation' => 'Development Officer',
            'office_id' => $office->id,
            'service_id' => $service->id,
        ])
        ->assertSessionHasErrors('office_id');

    expect(EmployeeProfile::count())->toBe(0);
});

test('a user without the employee role cannot create an employee profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('employee-profile.update'), [])
        ->assertForbidden();
});
