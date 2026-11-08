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

    $employeeRole = Role::findOrCreate('Employee', 'web');

    $employeeRole->syncPermissions([
        $viewPermission,
        $createPermission,
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createApplicationEmployee(string $nic): User
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

function createOpenTransferCycle(): TransferCycle
{
    return TransferCycle::create([
        'name' => 'Test Transfer Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);
}

function createApplicationOffice(string $code, string $name): Office
{
    return Office::create([
        'code' => $code,
        'name' => $name,
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);
}

function draftData(Office $currentOffice, Service $service): array
{
    return [
        'full_name' => 'Test Employee',

        'name_with_initials_sinhala' => null,

        'name_with_initials_english' => 'T. Employee',

        'date_of_birth' => '1990-01-01',

        'gender' => 'female',

        'permanent_address' => 'Test address',

        'temporary_address' => null,

        'office_phone' => null,

        'personal_phone' => '0771234567',

        'marital_status' => 'unmarried',

        'spouse_name' => null,

        'spouse_occupation' => null,

        'spouse_workplace' => null,

        'post' => 'Development Officer',

        'grade' => 'III',

        'appointment_letter_no' => 'TEST-001',

        'is_confirmed' => true,

        'current_office_id' => $currentOffice->id,

        'service_id' => $service->id,

        'reported_to_current_office_on' => '2020-01-01',

        'accept_nearby_office' => false,

        'transfer_reason' => 'Test reason',

        'children' => [
            [
                'name' => 'Test Child',
                'age' => 8,
                'school' => 'Test School',
            ],
        ],

        'work_history' => [
            [
                'workplace_name' => 'Previous Office',
                'from_date' => '2018-01-01',
                'to_date' => '2019-12-31',
            ],
        ],

        'preferences' => [],
    ];
}

test('an employee can start one draft in an open cycle', function () {
    $employee = createApplicationEmployee('901234567V');

    $cycle = createOpenTransferCycle();

    $this->actingAs($employee)
        ->post(route('transfer-applications.store'), [
            'transfer_cycle_id' => $cycle->id,
        ])
        ->assertRedirect();

    $application = TransferApplication::query()
        ->where('user_id', $employee->id)
        ->where('transfer_cycle_id', $cycle->id)
        ->firstOrFail();

    expect($application->status)->toBe('draft');

    $this->actingAs($employee)
        ->post(route('transfer-applications.store'), [
            'transfer_cycle_id' => $cycle->id,
        ])
        ->assertRedirect(
            route('transfer-applications.edit', $application)
        );

    expect(
        TransferApplication::query()
            ->where('user_id', $employee->id)
            ->where('transfer_cycle_id', $cycle->id)
            ->count()
    )->toBe(1);
});

test('an employee cannot start a draft in a closed cycle', function () {
    $employee = createApplicationEmployee('901234567V');

    $cycle = TransferCycle::create([
        'name' => 'Closed Cycle',
        'opens_on' => now('Asia/Colombo')->subDays(10)->toDateString(),
        'closes_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'status' => 'closed',
    ]);

    $this->actingAs($employee)
        ->from(route('transfer-applications.index'))
        ->post(route('transfer-applications.store'), [
            'transfer_cycle_id' => $cycle->id,
        ])
        ->assertSessionHasErrors('transfer_cycle_id');

    expect(TransferApplication::count())->toBe(0);
});

test('an employee can save a draft with related information', function () {
    $employee = createApplicationEmployee('901234567V');

    $cycle = createOpenTransferCycle();

    $currentOffice = createApplicationOffice(
        'OFF-01',
        'Current Office'
    );

    $preferredOffice = createApplicationOffice(
        'OFF-02',
        'Preferred Office'
    );

    $service = Service::create([
        'code' => 'DO',
        'name' => 'Development Officer',
        'is_active' => true,
    ]);

    $application = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $employee->id,
        'reference_no' => 'TEST-APPLICATION-001',
        'status' => 'draft',
    ]);

    $data = draftData($currentOffice, $service);

    $data['preferences'] = [
        [
            'office_id' => $preferredOffice->id,
            'reason' => 'Closer to home',
        ],
    ];

    $this->actingAs($employee)
        ->put(
            route('transfer-applications.update', $application),
            $data
        )
        ->assertRedirect(
            route('transfer-applications.edit', $application)
        )
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('transfer_applications', [
        'id' => $application->id,
        'full_name' => 'Test Employee',
        'current_office_id' => $currentOffice->id,
        'service_id' => $service->id,
        'status' => 'draft',
    ]);

    $this->assertDatabaseHas('transfer_application_children', [
        'transfer_application_id' => $application->id,
        'name' => 'Test Child',
    ]);

    $this->assertDatabaseHas('transfer_application_work_histories', [
        'transfer_application_id' => $application->id,
        'workplace_name' => 'Previous Office',
    ]);

    $this->assertDatabaseHas('transfer_application_preferences', [
        'transfer_application_id' => $application->id,
        'office_id' => $preferredOffice->id,
        'priority' => 1,
    ]);
});

test('an employee cannot view another employee draft', function () {
    $owner = createApplicationEmployee('901234567V');

    $otherEmployee = createApplicationEmployee('902345678V');

    $cycle = createOpenTransferCycle();

    $application = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $owner->id,
        'reference_no' => 'TEST-APPLICATION-002',
        'status' => 'draft',
    ]);

    $this->actingAs($otherEmployee)
        ->get(route('transfer-applications.edit', $application))
        ->assertForbidden();
});

test('duplicate preferred offices are rejected', function () {
    $employee = createApplicationEmployee('901234567V');

    $cycle = createOpenTransferCycle();

    $currentOffice = createApplicationOffice(
        'OFF-01',
        'Current Office'
    );

    $preferredOffice = createApplicationOffice(
        'OFF-02',
        'Preferred Office'
    );

    $service = Service::create([
        'code' => 'DO',
        'name' => 'Development Officer',
        'is_active' => true,
    ]);

    $application = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $employee->id,
        'reference_no' => 'TEST-APPLICATION-003',
        'status' => 'draft',
    ]);

    $data = draftData($currentOffice, $service);

    $data['preferences'] = [
        [
            'office_id' => $preferredOffice->id,
            'reason' => null,
        ],
        [
            'office_id' => $preferredOffice->id,
            'reason' => null,
        ],
    ];

    $this->actingAs($employee)
        ->from(route('transfer-applications.edit', $application))
        ->put(
            route('transfer-applications.update', $application),
            $data
        )
        ->assertSessionHasErrors();

    expect($application->preferences()->count())->toBe(0);
});

test('the current office cannot be selected as a preferred office', function () {
    $employee = createApplicationEmployee('901234567V');

    $cycle = createOpenTransferCycle();

    $currentOffice = createApplicationOffice(
        'OFF-01',
        'Current Office'
    );

    $service = Service::create([
        'code' => 'DO',
        'name' => 'Development Officer',
        'is_active' => true,
    ]);

    $application = TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $employee->id,
        'reference_no' => 'TEST-APPLICATION-004',
        'status' => 'draft',
    ]);

    $data = draftData($currentOffice, $service);

    $data['preferences'] = [
        [
            'office_id' => $currentOffice->id,
            'reason' => null,
        ],
    ];

    $this->actingAs($employee)
        ->from(route('transfer-applications.edit', $application))
        ->put(
            route('transfer-applications.update', $application),
            $data
        )
        ->assertSessionHasErrors('preferences.0.office_id');
});
