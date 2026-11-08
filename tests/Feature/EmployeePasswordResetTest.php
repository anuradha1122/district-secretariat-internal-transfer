<?php

use App\Models\EligibleEmployee;
use App\Models\EmployeePasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['session.driver' => 'database']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('manage eligible employees', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('an authorized administrator can issue a reset code', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage eligible employees');

    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $user = User::factory()->make(['email' => null]);
    $user->eligible_employee_id = $employee->id;
    $user->save();

    $this->actingAs($admin)
        ->post(route('employee-reset-codes.issue'), [
            'nic' => '901234567v',
        ])
        ->assertRedirect(route('employee-reset-codes.index'));

    $issued = session('issued_employee_password_reset_code');

    expect($issued['code'])->toHaveLength(24);

    $this->assertDatabaseHas('employee_password_reset_codes', [
        'eligible_employee_id' => $employee->id,
        'code_hash' => hash('sha256', $issued['code']),
    ]);
});

test('a valid reset code changes the password and cannot be reused', function () {
    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $user = User::factory()->make([
        'email' => null,
        'password' => Hash::make('OldPassword123!'),
    ]);

    $user->eligible_employee_id = $employee->id;
    $user->save();

    $plainCode = 'ABCDEF123456ABCDEF123456';

    EmployeePasswordResetCode::create([
        'eligible_employee_id' => $employee->id,
        'code_hash' => hash('sha256', $plainCode),
        'expires_at' => now()->addDay(),
    ]);

    $data = [
        'nic' => '901234567V',
        'reset_code' => $plainCode,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ];

    $this->post(route('employee-password-reset.store'), $data)
        ->assertRedirect(route('login'));

    expect(Hash::check('NewPassword123!', $user->fresh()->password))
        ->toBeTrue();

    $this->post(route('employee-password-reset.store'), $data)
        ->assertSessionHasErrors('reset_code');
});

test('an expired reset code is rejected', function () {
    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $user = User::factory()->make(['email' => null]);
    $user->eligible_employee_id = $employee->id;
    $user->save();

    $plainCode = 'ABCDEF123456ABCDEF123456';

    EmployeePasswordResetCode::create([
        'eligible_employee_id' => $employee->id,
        'code_hash' => hash('sha256', $plainCode),
        'expires_at' => now()->subMinute(),
    ]);

    $this->post(route('employee-password-reset.store'), [
        'nic' => '901234567V',
        'reset_code' => $plainCode,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertSessionHasErrors('reset_code');
});

test('an unauthorized user cannot issue a reset code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('employee-reset-codes.issue'), [
            'nic' => '901234567V',
        ])
        ->assertForbidden();

    expect(EmployeePasswordResetCode::count())->toBe(0);
});
