<?php

use App\Models\EligibleEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('an employee without email can log in using NIC', function () {
    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $user = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
        'password' => Hash::make('StrongPassword123!'),
    ]);

    $user->eligible_employee_id = $employee->id;
    $user->save();

    $this->post('/login', [
        'email' => '901234567v',
        'password' => 'StrongPassword123!',
    ]);

    $this->assertAuthenticatedAs($user);

    $this->get('/dashboard')->assertOk();
});

test('an inactive employee cannot log in with NIC', function () {
    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => false,
    ]);

    $user = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
        'password' => Hash::make('StrongPassword123!'),
    ]);

    $user->eligible_employee_id = $employee->id;
    $user->save();

    $this->post('/login', [
        'email' => '901234567V',
        'password' => 'StrongPassword123!',
    ]);

    $this->assertGuest();
});

test('an administrator can continue logging in with email', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('StrongPassword123!'),
    ]);

    $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'StrongPassword123!',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('an inactive employee with an existing session is blocked', function () {
    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => false,
    ]);

    $user = User::factory()->make([
        'email' => null,
        'email_verified_at' => null,
    ]);

    $user->eligible_employee_id = $employee->id;
    $user->save();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertForbidden();
});
