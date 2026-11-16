<?php

use App\Models\EligibleEmployee;
use App\Models\MutualMatchRun;
use App\Models\Office;
use App\Models\Service;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('view mutual matches', 'web');
    Permission::findOrCreate('manage mutual matches', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function matchDecisionManager(): User
{
    $user = User::factory()->create();

    $user->givePermissionTo([
        'view mutual matches',
        'manage mutual matches',
    ]);

    return $user;
}

function matchDecisionEmployee(string $nic): User
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

    return $user;
}

function matchDecisionOffice(string $code): Office
{
    return Office::create([
        'code' => $code,
        'name' => 'Test Office '.$code,
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);
}

function matchDecisionCycle(): TransferCycle
{
    return TransferCycle::create([
        'name' => 'Match Decision Test Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);
}

function matchDecisionService(): Service
{
    return Service::create([
        'code' => 'MATCH-TEST',
        'name' => 'Match Decision Test Service',
        'is_active' => true,
    ]);
}

function matchDecisionApplication(
    User $user,
    TransferCycle $cycle,
    Service $service,
    Office $office,
    string $reference
): TransferApplication {
    return TransferApplication::create([
        'transfer_cycle_id' => $cycle->id,
        'user_id' => $user->id,
        'reference_no' => $reference,
        'full_name' => 'Test Applicant '.$reference,
        'current_office_id' => $office->id,
        'service_id' => $service->id,
        'status' => 'approved',
        'submitted_at' => now(),
    ]);
}

/**
 * Create two approved reciprocal applications and save a matching run.
 *
 * @return array{
 *     manager: User,
 *     cycle: TransferCycle,
 *     service: Service,
 *     officeA: Office,
 *     officeB: Office,
 *     userA: User,
 *     userB: User,
 *     applicationA: TransferApplication,
 *     applicationB: TransferApplication,
 *     run: MutualMatchRun
 * }
 */
function matchDecisionFixture(): array
{
    $manager = matchDecisionManager();

    $cycle = matchDecisionCycle();
    $service = matchDecisionService();

    $officeA = matchDecisionOffice('DEC-A');
    $officeB = matchDecisionOffice('DEC-B');

    $userA = matchDecisionEmployee('199012345678');
    $userB = matchDecisionEmployee('199112345678');

    $applicationA = matchDecisionApplication(
        $userA,
        $cycle,
        $service,
        $officeA,
        'MATCH-DEC-A'
    );

    $applicationB = matchDecisionApplication(
        $userB,
        $cycle,
        $service,
        $officeB,
        'MATCH-DEC-B'
    );

    $applicationA->preferences()->create([
        'office_id' => $officeB->id,
        'priority' => 1,
        'reason' => 'Test preference A',
    ]);

    $applicationB->preferences()->create([
        'office_id' => $officeA->id,
        'priority' => 2,
        'reason' => 'Test preference B',
    ]);

    test()->actingAs($manager)
        ->post(route('mutual-match-runs.store'), [
            'cycle_id' => $cycle->id,
            'service_id' => $service->id,
        ])
        ->assertSessionHasNoErrors();

    $run = MutualMatchRun::query()->latest('id')->firstOrFail();

    return [
        'manager' => $manager,
        'cycle' => $cycle,
        'service' => $service,
        'officeA' => $officeA,
        'officeB' => $officeB,
        'userA' => $userA,
        'userB' => $userB,
        'applicationA' => $applicationA,
        'applicationB' => $applicationB,
        'run' => $run,
    ];
}

function matchDecisionPayload(
    TransferApplication $applicationA,
    TransferApplication $applicationB,
    string $outcome = 'confirmed',
    ?string $remarks = null
): array {
    return [
        'application_a_id' => $applicationA->id,
        'application_b_id' => $applicationB->id,
        'outcome' => $outcome,
        'remarks' => $remarks,
    ];
}

test('an authorized reviewer can confirm a current eligible pair', function () {
    $fixture = matchDecisionFixture();

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertRedirect(
            route('mutual-match-runs.show', $fixture['run'])
        )
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mutual_match_decisions', [
        'mutual_match_run_id' => $fixture['run']->id,
        'application_a_id' => $fixture['applicationA']->id,
        'application_b_id' => $fixture['applicationB']->id,
        'reviewer_id' => $fixture['manager']->id,
        'outcome' => 'confirmed',
    ]);

    $this->assertDatabaseHas('mutual_match_participants', [
        'transfer_cycle_id' => $fixture['cycle']->id,
        'user_id' => $fixture['userA']->id,
        'transfer_application_id' => $fixture['applicationA']->id,
    ]);

    $this->assertDatabaseHas('mutual_match_participants', [
        'transfer_cycle_id' => $fixture['cycle']->id,
        'user_id' => $fixture['userB']->id,
        'transfer_application_id' => $fixture['applicationB']->id,
    ]);

    $this->assertDatabaseCount('mutual_match_participants', 2);
});

test('a reviewer can reject a pair with a reason', function () {
    $fixture = matchDecisionFixture();

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB'],
                'rejected',
                'The proposed pair is not suitable.'
            )
        )
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mutual_match_decisions', [
        'mutual_match_run_id' => $fixture['run']->id,
        'outcome' => 'rejected',
        'remarks' => 'The proposed pair is not suitable.',
    ]);

    $this->assertDatabaseCount('mutual_match_participants', 0);
});

test('rejecting a pair requires a reason', function () {
    $fixture = matchDecisionFixture();

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB'],
                'rejected',
                ''
            )
        )
        ->assertSessionHasErrors('remarks');

    $this->assertDatabaseCount('mutual_match_decisions', 0);
    $this->assertDatabaseCount('mutual_match_participants', 0);
});

test('a user without manage permission cannot decide a pair', function () {
    $fixture = matchDecisionFixture();

    $unauthorized = User::factory()->create();

    $this->actingAs($unauthorized)
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertForbidden();

    $this->assertDatabaseCount('mutual_match_decisions', 0);
});

test('a reviewer cannot decide on a pair containing their own application', function () {
    $fixture = matchDecisionFixture();

    $fixture['userA']->givePermissionTo('manage mutual matches');

    $this->actingAs($fixture['userA'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertForbidden();

    $this->assertDatabaseCount('mutual_match_decisions', 0);
});

test('a pair outside the saved run cannot be decided', function () {
    $fixture = matchDecisionFixture();

    $otherEmployee = matchDecisionEmployee('199212345678');

    $otherApplication = matchDecisionApplication(
        $otherEmployee,
        $fixture['cycle'],
        $fixture['service'],
        $fixture['officeB'],
        'MATCH-DEC-C'
    );

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $otherApplication
            )
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 0);
});

test('a pair cannot be decided twice in the same run', function () {
    $fixture = matchDecisionFixture();

    $payload = matchDecisionPayload(
        $fixture['applicationA'],
        $fixture['applicationB'],
        'rejected',
        'First decision.'
    );

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            $payload
        )
        ->assertSessionHasNoErrors();

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            $payload
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 1);
});

test('a changed application status makes the pair stale', function () {
    $fixture = matchDecisionFixture();

    $fixture['applicationB']->update([
        'status' => 'rejected',
    ]);

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 0);
    $this->assertDatabaseCount('mutual_match_participants', 0);
});

test('an inactive eligible employee makes the pair stale', function () {
    $fixture = matchDecisionFixture();

    EligibleEmployee::query()
        ->whereKey($fixture['userB']->eligible_employee_id)
        ->update(['is_active' => false]);

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 0);
});

test('a changed current office makes the pair stale', function () {
    $fixture = matchDecisionFixture();

    $newOffice = matchDecisionOffice('DEC-C');

    $fixture['applicationB']->update([
        'current_office_id' => $newOffice->id,
    ]);

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 0);
});

test('a removed reciprocal preference makes the pair stale', function () {
    $fixture = matchDecisionFixture();

    $fixture['applicationB']->preferences()->delete();

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 0);
});

test('a changed reciprocal preference rank makes the pair stale', function () {
    $fixture = matchDecisionFixture();

    $fixture['applicationB']->preferences()->update([
        'priority' => 3,
    ]);

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 0);
});

test('an employee cannot be confirmed again in another saved run', function () {
    $fixture = matchDecisionFixture();

    /*
     * Save the same current candidate in another run before confirming.
     */
    $this->actingAs($fixture['manager'])
        ->post(route('mutual-match-runs.store'), [
            'cycle_id' => $fixture['cycle']->id,
            'service_id' => $fixture['service']->id,
        ])
        ->assertSessionHasNoErrors();

    $secondRun = MutualMatchRun::query()
        ->where('id', '!=', $fixture['run']->id)
        ->latest('id')
        ->firstOrFail();

    $payload = matchDecisionPayload(
        $fixture['applicationA'],
        $fixture['applicationB']
    );

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            $payload
        )
        ->assertSessionHasNoErrors();

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $secondRun),
            $payload
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 1);
    $this->assertDatabaseCount('mutual_match_participants', 2);
});

test('the database rejects duplicate confirmed participants in one cycle', function () {
    $fixture = matchDecisionFixture();

    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasNoErrors();

    $participant = DB::table('mutual_match_participants')
        ->where('user_id', $fixture['userA']->id)
        ->first();

    expect($participant)->not->toBeNull();

    expect(fn () => DB::table('mutual_match_participants')->insert([
        'mutual_match_decision_id' => $participant->mutual_match_decision_id,
        'transfer_cycle_id' => $participant->transfer_cycle_id,
        'user_id' => $participant->user_id,
        'transfer_application_id' => $participant->transfer_application_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});


test('an employee cannot be confirmed with a different partner in another run', function () {
    $fixture = matchDecisionFixture();

    /*
     * Employee C works in the same office as employee B and also
     * prefers employee A's office.
     *
     * Both A–B and A–C are therefore reciprocal candidates.
     */
    $userC = matchDecisionEmployee('199212345678');

    $applicationC = matchDecisionApplication(
        $userC,
        $fixture['cycle'],
        $fixture['service'],
        $fixture['officeB'],
        'MATCH-DEC-C'
    );

    $applicationC->preferences()->create([
        'office_id' => $fixture['officeA']->id,
        'priority' => 1,
        'reason' => 'Test preference C',
    ]);

    /*
     * Save a second run while both candidate pairs are available.
     */
    $this->actingAs($fixture['manager'])
        ->post(route('mutual-match-runs.store'), [
            'cycle_id' => $fixture['cycle']->id,
            'service_id' => $fixture['service']->id,
        ])
        ->assertSessionHasNoErrors();

    $secondRun = MutualMatchRun::query()
        ->where('id', '!=', $fixture['run']->id)
        ->latest('id')
        ->firstOrFail();

    $secondRunPairs = collect($secondRun->candidate_snapshot)
        ->map(fn (array $candidate) => [
            $candidate['application_a']['id'],
            $candidate['application_b']['id'],
        ])
        ->all();

    expect($secondRunPairs)->toContain([
        $fixture['applicationA']->id,
        $applicationC->id,
    ]);

    /*
     * Confirm A–B in the first run.
     */
    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasNoErrors();

    /*
     * Attempt to confirm A–C from the second run.
     * This must fail because A is already confirmed with B.
     */
    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $secondRun),
            matchDecisionPayload(
                $fixture['applicationA'],
                $applicationC
            )
        )
        ->assertSessionHasErrors('pair');

    $this->assertDatabaseCount('mutual_match_decisions', 1);
    $this->assertDatabaseCount('mutual_match_participants', 2);

    $this->assertDatabaseMissing('mutual_match_participants', [
        'transfer_cycle_id' => $fixture['cycle']->id,
        'user_id' => $userC->id,
    ]);
});

test('database prevents the same employee joining different confirmed decisions in one cycle', function () {
    $fixture = matchDecisionFixture();

    /*
     * Confirm A–B normally, creating their participant records.
     */
    $this->actingAs($fixture['manager'])
        ->post(
            route('mutual-match-decisions.store', $fixture['run']),
            matchDecisionPayload(
                $fixture['applicationA'],
                $fixture['applicationB']
            )
        )
        ->assertSessionHasNoErrors();

    /*
     * Create a different run and a different decision record.
     * This is test setup to isolate the participant constraint.
     */
    $secondRun = MutualMatchRun::create([
        'transfer_cycle_id' => $fixture['cycle']->id,
        'service_id' => $fixture['service']->id,
        'created_by' => $fixture['manager']->id,
        'algorithm_version' => 'reciprocal-greedy-v1',
        'input_count' => 0,
        'candidate_count' => 0,
        'input_snapshot' => [],
        'candidate_snapshot' => [],
        'generated_at' => now(),
    ]);

    $userC = matchDecisionEmployee('199212345678');

    $applicationC = matchDecisionApplication(
        $userC,
        $fixture['cycle'],
        $fixture['service'],
        $fixture['officeB'],
        'MATCH-DEC-C'
    );

    $secondDecision = \App\Models\MutualMatchDecision::create([
        'mutual_match_run_id' => $secondRun->id,
        'transfer_cycle_id' => $fixture['cycle']->id,
        'application_a_id' => $fixture['applicationA']->id,
        'application_b_id' => $applicationC->id,
        'reviewer_id' => $fixture['manager']->id,
        'outcome' => 'confirmed',
        'decided_at' => now(),
    ]);

    /*
     * A participant row for employee A already exists in the same
     * cycle, but under a different decision. The database must reject it.
     */
    expect(fn () => DB::table('mutual_match_participants')->insert([
        'mutual_match_decision_id' => $secondDecision->id,
        'transfer_cycle_id' => $fixture['cycle']->id,
        'user_id' => $fixture['userA']->id,
        'transfer_application_id' => $fixture['applicationA']->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
