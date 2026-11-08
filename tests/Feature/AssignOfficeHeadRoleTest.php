<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('Office Head', 'web');
    Role::findOrCreate('Employee', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('an existing user can receive the office head role without losing other roles', function () {
    $user = User::factory()->create();

    $user->assignRole('Employee');

    $this->artisan('users:assign-office-head')
        ->expectsChoice(
            'What would you like to do?',
            'Assign the role to an existing user',
            [
                'Assign the role to an existing user',
                'Create a new account and assign the role',
            ]
        )
        ->expectsQuestion('Enter the existing user ID', (string) $user->id)
        ->expectsConfirmation(
            'Assign the Office Head role to this user?',
            'yes'
        )
        ->assertSuccessful();

    $user->refresh();

    expect($user->hasRole('Employee'))->toBeTrue();
    expect($user->hasRole('Office Head'))->toBeTrue();
});

test('an existing user is not changed when confirmation is declined', function () {
    $user = User::factory()->create();

    $this->artisan('users:assign-office-head')
        ->expectsChoice(
            'What would you like to do?',
            'Assign the role to an existing user',
            [
                'Assign the role to an existing user',
                'Create a new account and assign the role',
            ]
        )
        ->expectsQuestion('Enter the existing user ID', (string) $user->id)
        ->expectsConfirmation(
            'Assign the Office Head role to this user?',
            'no'
        )
        ->assertSuccessful();

    expect($user->fresh()->hasRole('Office Head'))->toBeFalse();
});

test('a new verified office head account can be created', function () {
    $this->artisan('users:assign-office-head')
        ->expectsChoice(
            'What would you like to do?',
            'Create a new account and assign the role',
            [
                'Assign the role to an existing user',
                'Create a new account and assign the role',
            ]
        )
        ->expectsQuestion('Enter the Office Head’s name', 'Test Office Head')
        ->expectsQuestion('Enter their email address', 'head@example.test')
        ->expectsQuestion('Enter a password for the new account', 'Testing123456')
        ->expectsQuestion('Confirm the password', 'Testing123456')
        ->expectsConfirmation(
            'Have you independently verified this email address and authorized this account?',
            'yes'
        )
        ->assertSuccessful();

    $user = User::where('email', 'head@example.test')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('Office Head'))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('Testing123456', $user->password))->toBeTrue();
});

test('a new account is not created when email verification is declined', function () {
    $this->artisan('users:assign-office-head')
        ->expectsChoice(
            'What would you like to do?',
            'Create a new account and assign the role',
            [
                'Assign the role to an existing user',
                'Create a new account and assign the role',
            ]
        )
        ->expectsQuestion('Enter the Office Head’s name', 'Test Office Head')
        ->expectsQuestion('Enter their email address', 'head@example.test')
        ->expectsQuestion('Enter a password for the new account', 'Testing123456')
        ->expectsQuestion('Confirm the password', 'Testing123456')
        ->expectsConfirmation(
            'Have you independently verified this email address and authorized this account?',
            'no'
        )
        ->assertSuccessful();

    $this->assertDatabaseMissing('users', [
        'email' => 'head@example.test',
    ]);
});

test('a new account cannot use an existing email address', function () {
    User::factory()->create([
        'email' => 'existing@example.test',
    ]);

    $this->artisan('users:assign-office-head')
        ->expectsChoice(
            'What would you like to do?',
            'Create a new account and assign the role',
            [
                'Assign the role to an existing user',
                'Create a new account and assign the role',
            ]
        )
        ->expectsQuestion('Enter the Office Head’s name', 'Test Office Head')
        ->expectsQuestion('Enter their email address', 'existing@example.test')
        ->assertFailed();

    expect(User::where('email', 'existing@example.test')->count())->toBe(1);
});
