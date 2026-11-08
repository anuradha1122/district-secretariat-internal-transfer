<?php

use App\Models\EligibleEmployee;
use App\Models\Office;
use App\Models\Service;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $viewPermission = Permission::findOrCreate(
        'view own applications',
        'web'
    );

    $createPermission = Permission::findOrCreate(
        'create own applications',
        'web'
    );

    $role = Role::findOrCreate('Employee', 'web');

    $role->syncPermissions([
        $viewPermission,
        $createPermission,
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function submissionEmployee(string $nic): User
{
    $eligible = EligibleEmployee::create([
        'nic' => $nic,
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

function submissionCycle(string $status = 'open'): TransferCycle
{
    return TransferCycle::create([
        'name' => 'Test Transfer Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => $status,
    ]);
}

function completeDraft(User $user, TransferCycle $cycle): TransferApplication
{
    $currentOffice = Office::create([
        'code' => 'OFF-01',
        'name' => 'Current Office',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $preferredOffice = Office::create([
        'code' => 'OFF-02',
        'name' => 'Preferred Office',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $service = Service::create([
        'code' => 'DO',
        'name' => 'Development Officer',
        'is_active' => true,
    ]);

    $application = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $user->id,
        'reference_no' => 'TEST-SUBMISSION-001',

        'full_name' => 'Test Employee',
        'name_with_initials_english' => 'T. Employee',
        'date_of_birth' => '1990-01-01',
        'gender' => 'female',
        'permanent_address' => 'Test address',
        'personal_phone' => '0771234567',
        'marital_status' => 'unmarried',

        'post' => 'Development Officer',
        'grade' => 'III',
        'appointment_letter_no' => 'TEST-001',
        'is_confirmed' => true,

        'current_office_id' => $currentOffice->id,
        'service_id' => $service->id,
        'reported_to_current_office_on' => '2020-01-01',

        'accept_nearby_office' => false,
        'transfer_reason' => 'Test transfer reason',

        'status' => 'draft',
    ]);

    $application->preferences()->create([
        'office_id' => $preferredOffice->id,
        'priority' => 1,
        'reason' => 'Closer to home',
    ]);

    return $application;
}

test('an employee can submit a complete draft', function () {
    $employee = submissionEmployee('901234567V');

    $application = completeDraft(
        $employee,
        submissionCycle()
    );

    $this->actingAs($employee)
        ->post(route('transfer-applications.submit', $application), [
            'declaration_accepted' => true,
        ])
        ->assertRedirect(
            route('transfer-applications.review', $application)
        )
        ->assertSessionHasNoErrors();

    $application->refresh();

    expect($application->status)->toBe('submitted');
    expect($application->declaration_accepted)->toBeTrue();
    expect($application->submitted_at)->not->toBeNull();
});

test('an incomplete draft cannot be submitted', function () {
    $employee = submissionEmployee('901234567V');

    $cycle = submissionCycle();

    $application = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $employee->id,
        'reference_no' => 'TEST-INCOMPLETE-001',
        'status' => 'draft',
    ]);

    $this->actingAs($employee)
        ->from(route('transfer-applications.review', $application))
        ->post(route('transfer-applications.submit', $application), [
            'declaration_accepted' => true,
        ])
        ->assertSessionHasErrors('application');

    expect($application->fresh()->status)->toBe('draft');
});

test('the declaration must be accepted', function () {
    $employee = submissionEmployee('901234567V');

    $application = completeDraft(
        $employee,
        submissionCycle()
    );

    $this->actingAs($employee)
        ->from(route('transfer-applications.review', $application))
        ->post(route('transfer-applications.submit', $application), [
            'declaration_accepted' => false,
        ])
        ->assertSessionHasErrors('declaration_accepted');

    expect($application->fresh()->status)->toBe('draft');
});

test('a draft cannot be submitted when its cycle is closed', function () {
    $employee = submissionEmployee('901234567V');

    $application = completeDraft(
        $employee,
        submissionCycle('closed')
    );

    $this->actingAs($employee)
        ->from(route('transfer-applications.review', $application))
        ->post(route('transfer-applications.submit', $application), [
            'declaration_accepted' => true,
        ])
        ->assertSessionHasErrors('application');

    expect($application->fresh()->status)->toBe('draft');
});

test('an employee cannot submit another employee application', function () {
    $owner = submissionEmployee('901234567V');

    $otherEmployee = submissionEmployee('902345678V');

    $application = completeDraft(
        $owner,
        submissionCycle()
    );

    $this->actingAs($otherEmployee)
        ->post(route('transfer-applications.submit', $application), [
            'declaration_accepted' => true,
        ])
        ->assertForbidden();

    expect($application->fresh()->status)->toBe('draft');
});

test('a submitted application cannot be submitted again', function () {
    $employee = submissionEmployee('901234567V');

    $application = completeDraft(
        $employee,
        submissionCycle()
    );

    $this->actingAs($employee)
        ->post(route('transfer-applications.submit', $application), [
            'declaration_accepted' => true,
        ])
        ->assertRedirect();

    $this->actingAs($employee)
        ->from(route('transfer-applications.review', $application))
        ->post(route('transfer-applications.submit', $application), [
            'declaration_accepted' => true,
        ])
        ->assertSessionHasErrors('application');
});

test('a different employee cannot download the application PDF', function () {
    $owner = submissionEmployee('901234567V');

    $otherEmployee = submissionEmployee('902345678V');

    $application = completeDraft(
        $owner,
        submissionCycle()
    );

    $application->update([
        'status' => 'submitted',
        'submitted_at' => now(),
        'declaration_accepted' => true,
    ]);

    $this->actingAs($otherEmployee)
        ->get(route('transfer-applications.pdf', $application))
        ->assertForbidden();
});
