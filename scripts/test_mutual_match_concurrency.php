<?php

/*
 * MySQL concurrency check for mutual match decisions.
 *
 * This script uses a dedicated, empty database:
 * district_transfer_concurrency_test
 *
 * It creates test tables and synthetic data in that database.
 * It does not delete the database or run migrate:fresh.
 */

use App\Http\Controllers\MutualMatchDecisionController;
use App\Http\Controllers\MutualMatchRunController;
use App\Models\EligibleEmployee;
use App\Models\MutualMatchRun;
use App\Models\Office;
use App\Models\Service;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use App\Models\User;
use App\Services\Transfers\MutualTransferPreviewService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

const TEST_DATABASE = 'district_transfer_concurrency_run2';

function stopTest(string $message): never
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

function createTestUser(string $name, ?string $eligibleId = null): User
{
    $user = new User();

    $user->name = $name;
    $user->email = strtolower(str_replace(' ', '.', $name))
        .'@example.test';
    $user->password = Hash::make('TestPassword123!');
    $user->email_verified_at = now();

    if ($eligibleId !== null) {
        $user->eligible_employee_id = (int) $eligibleId;
    }

    $user->save();

    return $user;
}

function createEmployee(string $name, string $nic): User
{
    $eligible = EligibleEmployee::create([
        'nic' => $nic,
        'is_active' => true,
    ]);

    return createTestUser($name, (string) $eligible->id);
}

function createApplication(
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
        'full_name' => $user->name,
        'current_office_id' => $office->id,
        'service_id' => $service->id,
        'status' => 'approved',
        'submitted_at' => now(),
    ]);
}

function requestForUser(User $user, array $data): Request
{
    $request = Request::create('/', 'POST', $data);

    $request->setUserResolver(
        fn () => $user
    );

    return $request;
}

if (! extension_loaded('pdo_mysql')) {
    stopTest('PDO MySQL is not available.');
}

if (! function_exists('pcntl_fork')) {
    stopTest('PCNTL is not available.');
}

if (! function_exists('stream_socket_pair')) {
    stopTest('stream_socket_pair is not available.');
}

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';

/*
 * A cached configuration could contain connection settings that do not
 * reflect the expected environment. Refuse to run until it is cleared.
 */
if ($app->configurationIsCached()) {
    stopTest(
        'Configuration is cached. Run php artisan config:clear first.'
    );
}

$app->make(Kernel::class)->bootstrap();

/*
 * Override the database name in memory before opening a connection.
 * The connection's URL is cleared because a URL could otherwise
 * override the individual connection settings.
 */
config([
    'database.default' => 'mysql',
    'database.connections.mysql.url' => null,
    'database.connections.mysql.database' => TEST_DATABASE,
]);

DB::purge('mysql');
DB::setDefaultConnection('mysql');

try {
    $connection = DB::connection('mysql');

    $actualDatabase = $connection
        ->selectOne('SELECT DATABASE() AS database_name')
        ->database_name;
} catch (Throwable $exception) {
    stopTest(
        'Could not connect to the dedicated test database. '
        .'Check that it exists and that your MySQL user can access it.'
    );
}

if ($actualDatabase !== TEST_DATABASE) {
    stopTest('The connection is not using the dedicated test database.');
}

/*
 * Never run this script against an existing populated schema.
 */
$tableCount = (int) $connection
    ->table('information_schema.tables')
    ->where('table_schema', TEST_DATABASE)
    ->count();

if ($tableCount !== 0) {
    stopTest(
        'The dedicated test database is not empty. '
        .'Create a new empty database before running this script.'
    );
}

echo "Using dedicated database: ".TEST_DATABASE.PHP_EOL;
echo "Running normal migrations in the empty test database...".PHP_EOL;

$exitCode = Artisan::call('migrate', [
    '--database' => 'mysql',
    '--force' => true,
]);

echo Artisan::output();

if ($exitCode !== 0) {
    stopTest('The test database migrations failed.');
}

try {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $managePermission = Permission::findOrCreate(
        'manage mutual matches',
        'web'
    );

    $reviewerA = createTestUser('Test Reviewer A');
    $reviewerB = createTestUser('Test Reviewer B');

    $reviewerA->givePermissionTo($managePermission);
    $reviewerB->givePermissionTo($managePermission);

    $cycle = TransferCycle::create([
        'name' => 'Concurrency Test Cycle',
        'opens_on' => now('Asia/Colombo')->subDay()->toDateString(),
        'closes_on' => now('Asia/Colombo')->addDay()->toDateString(),
        'status' => 'open',
    ]);

    $service = Service::create([
        'code' => 'CONC-TEST',
        'name' => 'Concurrency Test Service',
        'is_active' => true,
    ]);

    $officeA = Office::create([
        'code' => 'CONC-A',
        'name' => 'Test Office A',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $officeB = Office::create([
        'code' => 'CONC-B',
        'name' => 'Test Office B',
        'type' => 'district_secretariat',
        'is_active' => true,
    ]);

    $userA = createEmployee('Test Employee A', '199012345678');
    $userB = createEmployee('Test Employee B', '199112345678');
    $userC = createEmployee('Test Employee C', '199212345678');

    $applicationA = createApplication(
        $userA,
        $cycle,
        $service,
        $officeA,
        'CONC-APPLICATION-A'
    );

    $applicationB = createApplication(
        $userB,
        $cycle,
        $service,
        $officeB,
        'CONC-APPLICATION-B'
    );

    $applicationC = createApplication(
        $userC,
        $cycle,
        $service,
        $officeB,
        'CONC-APPLICATION-C'
    );

    /*
     * A prefers Office B. Both B and C prefer Office A.
     * Therefore A–B and A–C are overlapping candidates.
     */
    $applicationA->preferences()->create([
        'office_id' => $officeB->id,
        'priority' => 1,
        'reason' => 'Concurrency test',
    ]);

    $applicationB->preferences()->create([
        'office_id' => $officeA->id,
        'priority' => 1,
        'reason' => 'Concurrency test',
    ]);

    $applicationC->preferences()->create([
        'office_id' => $officeA->id,
        'priority' => 1,
        'reason' => 'Concurrency test',
    ]);

    $runController = app(MutualMatchRunController::class);
    $previewService = app(MutualTransferPreviewService::class);

    /*
     * Create two separate runs. This avoids both requests being
     * serialized only by locking the same run row.
     */
    foreach ([1, 2] as $number) {
        $request = requestForUser($reviewerA, [
            'cycle_id' => $cycle->id,
            'service_id' => $service->id,
        ]);

        $runController->store($request, $previewService);
    }

    $runs = MutualMatchRun::query()
        ->orderBy('id')
        ->get();

    if ($runs->count() !== 2) {
        stopTest('The two matching runs were not created.');
    }

    $runA = $runs[0];
    $runB = $runs[1];

    $containsPair = function (
        MutualMatchRun $run,
        int $firstId,
        int $secondId
    ): bool {
        return collect($run->candidate_snapshot)
            ->contains(function (array $candidate) use ($firstId, $secondId): bool {
                return (int) ($candidate['application_a']['id'] ?? 0) === $firstId
                    && (int) ($candidate['application_b']['id'] ?? 0) === $secondId;
            });
    };

    if (
        ! $containsPair($runA, $applicationA->id, $applicationB->id)
        || ! $containsPair($runB, $applicationA->id, $applicationC->id)
    ) {
        stopTest('The expected overlapping candidate pairs were not generated.');
    }

    $jobs = [
        [
            'name' => 'A–B',
            'run_id' => $runA->id,
            'reviewer_id' => $reviewerA->id,
            'application_a_id' => $applicationA->id,
            'application_b_id' => $applicationB->id,
        ],
        [
            'name' => 'A–C',
            'run_id' => $runB->id,
            'reviewer_id' => $reviewerB->id,
            'application_a_id' => $applicationA->id,
            'application_b_id' => $applicationC->id,
        ],
    ];

    /*
     * Close the parent's connection before forking. Each child opens
     * its own MySQL connection after the fork.
     */
    DB::purge('mysql');

    $children = [];

    foreach ($jobs as $job) {
        $sockets = stream_socket_pair(
            STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );

        if ($sockets === false) {
            stopTest('Could not create process communication sockets.');
        }

        $pid = pcntl_fork();

        if ($pid === -1) {
            stopTest('Could not create a child process.');
        }

        if ($pid === 0) {
            fclose($sockets[0]);

            try {
                DB::purge('mysql');

                $childConnection = DB::connection('mysql');

                $childDatabase = $childConnection
                    ->selectOne('SELECT DATABASE() AS database_name')
                    ->database_name;

                if ($childDatabase !== TEST_DATABASE) {
                    throw new RuntimeException('Child connected to the wrong database.');
                }

                $reviewer = User::query()->findOrFail($job['reviewer_id']);
                $run = MutualMatchRun::query()->findOrFail($job['run_id']);

                /*
                 * Tell the parent this process is ready, then wait
                 * for the start signal.
                 */
                fwrite($sockets[1], "READY\n");

                $signal = fgets($sockets[1]);

                if (trim((string) $signal) !== 'GO') {
                    throw new RuntimeException('Start signal was not received.');
                }

                $request = requestForUser($reviewer, [
                    'application_a_id' => $job['application_a_id'],
                    'application_b_id' => $job['application_b_id'],
                    'outcome' => 'confirmed',
                    'remarks' => null,
                ]);

                app(MutualMatchDecisionController::class)
                    ->store($request, $run);

                $result = [
                    'pair' => $job['name'],
                    'result' => 'confirmed',
                ];
            } catch (ValidationException $exception) {
                $result = [
                    'pair' => $job['name'],
                    'result' => 'validation_rejected',
                    'errors' => $exception->errors(),
                ];
            } catch (Throwable $exception) {
                $result = [
                    'pair' => $job['name'],
                    'result' => 'error',
                    'message' => $exception->getMessage(),
                ];
            }

            fwrite(
                $sockets[1],
                json_encode($result, JSON_UNESCAPED_UNICODE)."\n"
            );

            fclose($sockets[1]);

            exit(0);
        }

        fclose($sockets[1]);

        stream_set_timeout($sockets[0], 30);

        $children[] = [
            'pid' => $pid,
            'socket' => $sockets[0],
        ];
    }

    /*
     * Wait until both processes have separate connections and are ready.
     */
    foreach ($children as $child) {
        $ready = fgets($child['socket']);

        if (trim((string) $ready) !== 'READY') {
            stopTest('A child process did not become ready.');
        }
    }

    echo "Both processes are ready. Starting confirmations...".PHP_EOL;

    foreach ($children as $child) {
        fwrite($child['socket'], "GO\n");
    }

    $results = [];

    foreach ($children as $child) {
        $line = fgets($child['socket']);

        if ($line === false) {
            stopTest('A child process did not return a result.');
        }

        $result = json_decode($line, true);

        if (! is_array($result)) {
            stopTest('A child process returned an invalid result.');
        }

        $results[] = $result;

        fclose($child['socket']);
        pcntl_waitpid($child['pid'], $status);
    }

    /*
     * Reconnect in the parent and inspect committed database records.
     */
    DB::purge('mysql');

    $confirmedCount = DB::table('mutual_match_decisions')
        ->where('outcome', 'confirmed')
        ->count();

    $participantCount = DB::table('mutual_match_participants')
        ->count();

    $employeeACount = DB::table('mutual_match_participants')
        ->where('transfer_cycle_id', $cycle->id)
        ->where('user_id', $userA->id)
        ->count();

    echo PHP_EOL."Process results:".PHP_EOL;

    foreach ($results as $result) {
        echo json_encode(
            $result,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ).PHP_EOL;
    }

    echo PHP_EOL;
    echo "Confirmed decisions: {$confirmedCount}".PHP_EOL;
    echo "Participant rows: {$participantCount}".PHP_EOL;
    echo "Employee A participant rows: {$employeeACount}".PHP_EOL;

    $successes = collect($results)
        ->where('result', 'confirmed')
        ->count();

    $rejections = collect($results)
        ->where('result', 'validation_rejected')
        ->count();

    if (
        $successes !== 1
        || $rejections !== 1
        || $confirmedCount !== 1
        || $participantCount !== 2
        || $employeeACount !== 1
    ) {
        stopTest('The concurrency result did not meet the expected outcome.');
    }

    echo PHP_EOL;
    echo "PASS: one overlapping pair was confirmed and the other was rejected.".PHP_EOL;
    echo "The dedicated test database and synthetic data have been retained.".PHP_EOL;
} catch (Throwable $exception) {
    stopTest($exception->getMessage());
}
