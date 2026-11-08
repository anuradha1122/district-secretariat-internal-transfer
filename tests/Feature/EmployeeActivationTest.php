<?php

use App\Models\EligibleEmployee;
use App\Models\EmployeeActivationCode;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('manage eligible employees', 'web');
    Role::findOrCreate('Employee', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('an authorized administrator can issue a code', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage eligible employees');

    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('activation-codes.issue', $employee));

    $response->assertRedirect(route('activation-codes.index'));

    $issued = session('issued_activation_code');

    expect($issued)->not->toBeNull();
    expect($issued['code'])->toHaveLength(24);

    $activation = EmployeeActivationCode::where(
        'eligible_employee_id',
        $employee->id
    )->firstOrFail();

    expect($activation->code_hash)->toBe(
        hash('sha256', $issued['code'])
    );

    expect($activation->code_hash)->not->toBe($issued['code']);
});

test('an employee can activate once using a valid code', function () {
    Notification::fake();

    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $plainCode = 'ABCDEF123456ABCDEF123456';

    EmployeeActivationCode::create([
        'eligible_employee_id' => $employee->id,
        'code_hash' => hash('sha256', $plainCode),
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->post(route('employee-activation.store'), [
        'nic' => '901234567v',
        'activation_code' => $plainCode,
        'name' => 'Test Employee',
        'email' => 'employee@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'employee@example.com')->firstOrFail();

    expect($user->eligible_employee_id)->toBe($employee->id);
    expect($user->hasRole('Employee'))->toBeTrue();

    $this->assertNotNull(
        EmployeeActivationCode::where(
            'eligible_employee_id',
            $employee->id
        )->firstOrFail()->used_at
    );

    Notification::assertSentTo($user, VerifyEmail::class);

    $this->post('/logout');

    $this->post(route('employee-activation.store'), [
        'nic' => '901234567V',
        'activation_code' => $plainCode,
        'name' => 'Another Employee',
        'email' => 'another@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertSessionHasErrors('activation_code');

    $this->assertDatabaseMissing('users', [
        'email' => 'another@example.com',
    ]);
});

test('an expired code cannot activate an account', function () {
    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $plainCode = 'ABCDEF123456ABCDEF123456';

    EmployeeActivationCode::create([
        'eligible_employee_id' => $employee->id,
        'code_hash' => hash('sha256', $plainCode),
        'expires_at' => now()->subMinute(),
    ]);

    $this->post(route('employee-activation.store'), [
        'nic' => '901234567V',
        'activation_code' => $plainCode,
        'name' => 'Test Employee',
        'email' => 'employee@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertSessionHasErrors('activation_code');

    $this->assertDatabaseMissing('users', [
        'email' => 'employee@example.com',
    ]);
});

test('an inactive eligible employee cannot activate', function () {
    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => false,
    ]);

    $plainCode = 'ABCDEF123456ABCDEF123456';

    EmployeeActivationCode::create([
        'eligible_employee_id' => $employee->id,
        'code_hash' => hash('sha256', $plainCode),
        'expires_at' => now()->addDay(),
    ]);

    $this->post(route('employee-activation.store'), [
        'nic' => '901234567V',
        'activation_code' => $plainCode,
        'name' => 'Test Employee',
        'email' => 'employee@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertSessionHasErrors('activation_code');
});

test('an unauthorized user cannot issue a code', function () {
    $user = User::factory()->create();

    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('activation-codes.issue', $employee))
        ->assertForbidden();

    expect(EmployeeActivationCode::count())->toBe(0);
});

test('an administrator can revoke an unused code', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage eligible employees');

    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    EmployeeActivationCode::create([
        'eligible_employee_id' => $employee->id,
        'code_hash' => hash('sha256', 'ABCDEF123456ABCDEF123456'),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->delete(route('activation-codes.revoke', $employee))
        ->assertRedirect(route('activation-codes.index'));

    $this->assertDatabaseMissing('employee_activation_codes', [
        'eligible_employee_id' => $employee->id,
    ]);
});
