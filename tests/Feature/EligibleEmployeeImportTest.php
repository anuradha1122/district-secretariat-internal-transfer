<?php

use App\Models\EligibleEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view eligible employees', 'web');
    Permission::findOrCreate('import eligible employees', 'web');
    Permission::findOrCreate('manage eligible employees', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('an authorized user can import valid NICs', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('import eligible employees');

    $file = UploadedFile::fake()->createWithContent(
        'nics.csv',
        "nic\n901234567v\n199012345678\n"
    );

    $this->actingAs($user)
        ->post(route('eligible-employees.import'), [
            'file' => $file,
        ])
        ->assertRedirect(route('eligible-employees.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('eligible_employees', [
        'nic' => '901234567V',
        'is_active' => true,
        'imported_by' => $user->id,
    ]);

    $this->assertDatabaseHas('eligible_employees', [
        'nic' => '199012345678',
    ]);
});

test('invalid NICs reject the whole file', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('import eligible employees');

    $file = UploadedFile::fake()->createWithContent(
        'nics.csv',
        "nic\n901234567V\ninvalid\n"
    );

    $this->actingAs($user)
        ->from(route('eligible-employees.index'))
        ->post(route('eligible-employees.import'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');

    expect(EligibleEmployee::count())->toBe(0);
});

test('duplicate NICs in a file reject the whole file', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('import eligible employees');

    $file = UploadedFile::fake()->createWithContent(
        'nics.csv',
        "nic\n901234567V\n901234567v\n"
    );

    $this->actingAs($user)
        ->post(route('eligible-employees.import'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');

    expect(EligibleEmployee::count())->toBe(0);
});

test('a user without permission cannot import NICs', function () {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->createWithContent(
        'nics.csv',
        "nic\n901234567V\n"
    );

    $this->actingAs($user)
        ->post(route('eligible-employees.import'), [
            'file' => $file,
        ])
        ->assertForbidden();

    expect(EligibleEmployee::count())->toBe(0);
});

test('a user without permission cannot change eligibility status', function () {
    $user = User::factory()->create();

    $employee = EligibleEmployee::create([
        'nic' => '901234567V',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('eligible-employees.status', $employee), [
            'is_active' => false,
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('eligible_employees', [
        'id' => $employee->id,
        'is_active' => true,
    ]);
});
