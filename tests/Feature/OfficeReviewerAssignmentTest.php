<?php

use App\Models\Office;
use App\Models\OfficeReviewerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('manage users', 'web');

    Role::findOrCreate('Office Head', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function assignmentManager(): User
{
    $user = User::factory()->create();

    $user->givePermissionTo('manage users');

    return $user;
}

function assignmentOfficeHead(): User
{
    $user = User::factory()->create();

    $user->assignRole('Office Head');

    return $user;
}

function assignmentOffice(bool $active = true): Office
{
    return Office::create([
        'code' => 'TEST-'.str()->upper(str()->random(8)),
        'name' => 'Test Office',
        'type' => 'district_secretariat',
        'is_active' => $active,
    ]);
}

test('a manager can assign an office head to an active office', function () {
    $manager = assignmentManager();
    $reviewer = assignmentOfficeHead();
    $office = assignmentOffice();

    $this->actingAs($manager)
        ->post(route('office-reviewer-assignments.store'), [
            'user_id' => $reviewer->id,
            'office_id' => $office->id,
        ])
        ->assertRedirect(route('office-reviewer-assignments.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('office_reviewer_assignments', [
        'user_id' => $reviewer->id,
        'office_id' => $office->id,
        'is_active' => true,
        'assigned_by' => $manager->id,
    ]);
});

test('a user without permission cannot view or manage assignments', function () {
    $user = User::factory()->create();
    $reviewer = assignmentOfficeHead();
    $office = assignmentOffice();

    $this->actingAs($user)
        ->get(route('office-reviewer-assignments.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('office-reviewer-assignments.store'), [
            'user_id' => $reviewer->id,
            'office_id' => $office->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('office_reviewer_assignments', 0);
});

test('the selected reviewer must have the office head role', function () {
    $manager = assignmentManager();
    $user = User::factory()->create();
    $office = assignmentOffice();

    $this->actingAs($manager)
        ->post(route('office-reviewer-assignments.store'), [
            'user_id' => $user->id,
            'office_id' => $office->id,
        ])
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('office_reviewer_assignments', 0);
});

test('an inactive office cannot receive a new assignment', function () {
    $manager = assignmentManager();
    $reviewer = assignmentOfficeHead();
    $office = assignmentOffice(false);

    $this->actingAs($manager)
        ->post(route('office-reviewer-assignments.store'), [
            'user_id' => $reviewer->id,
            'office_id' => $office->id,
        ])
        ->assertSessionHasErrors('office_id');

    $this->assertDatabaseCount('office_reviewer_assignments', 0);
});

test('an active assignment cannot be duplicated', function () {
    $manager = assignmentManager();
    $reviewer = assignmentOfficeHead();
    $office = assignmentOffice();

    OfficeReviewerAssignment::create([
        'user_id' => $reviewer->id,
        'office_id' => $office->id,
        'is_active' => true,
        'assigned_by' => $manager->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('office-reviewer-assignments.store'), [
            'user_id' => $reviewer->id,
            'office_id' => $office->id,
        ])
        ->assertSessionHasErrors('office_id');

    $this->assertDatabaseCount('office_reviewer_assignments', 1);
});

test('a manager can revoke and reactivate an assignment', function () {
    $manager = assignmentManager();
    $reviewer = assignmentOfficeHead();
    $office = assignmentOffice();

    $assignment = OfficeReviewerAssignment::create([
        'user_id' => $reviewer->id,
        'office_id' => $office->id,
        'is_active' => true,
        'assigned_by' => $manager->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($manager)
        ->delete(route('office-reviewer-assignments.destroy', $assignment))
        ->assertRedirect(route('office-reviewer-assignments.index'));

    $this->assertDatabaseHas('office_reviewer_assignments', [
        'id' => $assignment->id,
        'is_active' => false,
        'revoked_by' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->post(route('office-reviewer-assignments.store'), [
            'user_id' => $reviewer->id,
            'office_id' => $office->id,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('office_reviewer_assignments', [
        'id' => $assignment->id,
        'is_active' => true,
        'revoked_by' => null,
    ]);

    $this->assertDatabaseCount('office_reviewer_assignments', 1);
});
