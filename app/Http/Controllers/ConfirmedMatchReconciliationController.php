<?php

namespace App\Http\Controllers;

use App\Services\Transfers\ConfirmedPairConsistencyChecker;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmedMatchReconciliationController extends Controller
{
    public function index(
        Request $request,
        ConfirmedPairConsistencyChecker $checker
    ): Response {
        abort_unless(
            $request->user()?->can('view mutual matches'),
            403
        );

        /*
         * Section 1: current participant-level issues.
         */
        $items = DB::table('mutual_match_decisions as decisions')
            ->join(
                'mutual_match_runs as runs',
                'runs.id',
                '=',
                'decisions.mutual_match_run_id'
            )
            ->join(
                'transfer_cycles as cycles',
                'cycles.id',
                '=',
                'decisions.transfer_cycle_id'
            )
            ->join(
                'mutual_match_participants as participants',
                'participants.mutual_match_decision_id',
                '=',
                'decisions.id'
            )
            ->join(
                'users',
                'users.id',
                '=',
                'participants.user_id'
            )
            ->join(
                'transfer_applications as applications',
                'applications.id',
                '=',
                'participants.transfer_application_id'
            )
            ->leftJoin(
                'eligible_employees as eligible',
                'eligible.id',
                '=',
                'users.eligible_employee_id'
            )
            ->where('decisions.outcome', 'confirmed')
            ->where(function ($query): void {
                $query
                    ->whereNull('eligible.id')
                    ->orWhere('eligible.is_active', false)
                    ->orWhere('applications.status', '!=', 'approved')
                    ->orWhereColumn(
                        'applications.transfer_cycle_id',
                        '!=',
                        'runs.transfer_cycle_id'
                    )
                    ->orWhereColumn(
                        'applications.service_id',
                        '!=',
                        'runs.service_id'
                    )
                    ->orWhereNull('applications.service_id');
            })
            ->orderByDesc('decisions.id')
            ->orderBy('participants.id')
            ->select([
                'decisions.id as decision_id',
                'decisions.mutual_match_run_id as run_id',
                'decisions.decided_at',
                'cycles.name as cycle_name',

                'participants.user_id',

                'applications.reference_no',
                'applications.full_name',
                'applications.status as application_status',
                'applications.transfer_cycle_id as application_cycle_id',
                'applications.service_id as application_service_id',

                'runs.transfer_cycle_id as run_cycle_id',
                'runs.service_id as run_service_id',

                'eligible.id as eligible_employee_id',
                'eligible.is_active',
            ])
            ->paginate(20, ['*'], 'participant_page')
            ->withQueryString()
            ->through(function ($item): array {
                $issues = [];

                if ($item->eligible_employee_id === null) {
                    $issues[] = 'Eligibility record missing';
                } elseif (! $item->is_active) {
                    $issues[] = 'Employee eligibility is inactive';
                }

                if ($item->application_status !== 'approved') {
                    $issues[] = 'Application is no longer approved';
                }

                if (
                    (int) $item->application_cycle_id
                    !== (int) $item->run_cycle_id
                ) {
                    $issues[] = 'Application cycle differs from the saved run';
                }

                if (
                    $item->application_service_id === null
                    || (int) $item->application_service_id
                        !== (int) $item->run_service_id
                ) {
                    $issues[] = 'Application service differs from the saved run';
                }

                return [
                    'decision_id' => $item->decision_id,
                    'run_id' => $item->run_id,
                    'decided_at' => $item->decided_at,
                    'cycle_name' => $item->cycle_name,
                    'user_id' => $item->user_id,
                    'reference_no' => $item->reference_no,
                    'full_name' => $item->full_name,
                    'issue' => implode('; ', $issues),
                ];
            });

        /*
         * Section 2: compare the saved candidate with current offices
         * and preferences.
         *
         * Decisions are processed in batches so application and preference
         * records are fetched in groups.
         */
        $pairIssues = [];

        DB::table('mutual_match_decisions as decisions')
            ->join(
                'mutual_match_runs as runs',
                'runs.id',
                '=',
                'decisions.mutual_match_run_id'
            )
            ->join(
                'transfer_cycles as cycles',
                'cycles.id',
                '=',
                'decisions.transfer_cycle_id'
            )
            ->where('decisions.outcome', 'confirmed')
            ->select([
                'decisions.id as decision_id',
                'decisions.mutual_match_run_id as run_id',
                'decisions.application_a_id',
                'decisions.application_b_id',
                'decisions.decided_at',
                'cycles.name as cycle_name',
                'runs.candidate_snapshot',
            ])
            ->orderBy('decisions.id')
            ->chunk(200, function ($decisions) use (
                $checker,
                &$pairIssues
            ): void {
                $applicationIds = $decisions
                    ->flatMap(fn ($decision) => [
                        $decision->application_a_id,
                        $decision->application_b_id,
                    ])
                    ->unique()
                    ->values()
                    ->all();

                $applications = DB::table('transfer_applications')
                    ->whereIn('id', $applicationIds)
                    ->get([
                        'id',
                        'reference_no',
                        'full_name',
                        'current_office_id',
                    ])
                    ->keyBy('id');

                $preferences = DB::table('transfer_application_preferences')
                    ->whereIn('transfer_application_id', $applicationIds)
                    ->orderBy('priority')
                    ->get([
                        'transfer_application_id',
                        'office_id',
                        'priority',
                    ])
                    ->groupBy('transfer_application_id');

                $currentApplication = function (int $id) use (
                    $applications,
                    $preferences
                ): ?array {
                    $application = $applications->get($id);

                    if (! $application) {
                        return null;
                    }

                    return [
                        'id' => $application->id,
                        'current_office_id' => $application->current_office_id,

                        'preferences' => $preferences
                            ->get($id, collect())
                            ->map(fn ($preference) => [
                                'office_id' => $preference->office_id,
                                'priority' => $preference->priority,
                            ])
                            ->values()
                            ->all(),
                    ];
                };

                foreach ($decisions as $decision) {
                    $snapshot = json_decode(
                        (string) $decision->candidate_snapshot,
                        true
                    );

                    $snapshot = is_array($snapshot)
                        ? $snapshot
                        : [];

                    $candidate = collect($snapshot)
                        ->first(function ($item) use ($decision): bool {
                            if (! is_array($item)) {
                                return false;
                            }

                            return (int) ($item['application_a']['id'] ?? 0)
                                    === (int) $decision->application_a_id
                                && (int) ($item['application_b']['id'] ?? 0)
                                    === (int) $decision->application_b_id;
                        });

                    $applicationA = $currentApplication(
                        (int) $decision->application_a_id
                    );

                    $applicationB = $currentApplication(
                        (int) $decision->application_b_id
                    );

                    /*
                     * An absent candidate is treated as an incomplete
                     * snapshot, not silently considered valid.
                     */
                    $issues = $checker->check(
                        is_array($candidate) ? $candidate : [],
                        $applicationA,
                        $applicationB
                    );

                    if ($issues === []) {
                        continue;
                    }

                    $displayA = $applications->get(
                        $decision->application_a_id
                    );

                    $displayB = $applications->get(
                        $decision->application_b_id
                    );

                    $pairIssues[] = [
                        'decision_id' => $decision->decision_id,
                        'run_id' => $decision->run_id,
                        'cycle_name' => $decision->cycle_name,
                        'decided_at' => $decision->decided_at,

                        'application_a' => [
                            'id' => $decision->application_a_id,
                            'reference_no' => $displayA?->reference_no,
                            'full_name' => $displayA?->full_name,
                        ],

                        'application_b' => [
                            'id' => $decision->application_b_id,
                            'reference_no' => $displayB?->reference_no,
                            'full_name' => $displayB?->full_name,
                        ],

                        'issues' => $issues,
                    ];
                }
            });

        /*
         * Sort newest decisions first, then paginate the identified issues.
         */
        usort(
            $pairIssues,
            fn (array $a, array $b): int =>
                $b['decision_id'] <=> $a['decision_id']
        );

        $perPage = 20;

        $currentPage = max(
            1,
            (int) $request->query('pair_page', 1)
        );

        $pairPaginator = new LengthAwarePaginator(
            array_slice(
                $pairIssues,
                ($currentPage - 1) * $perPage,
                $perPage
            ),
            count($pairIssues),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'pair_page',
            ]
        );

        $pairPaginator->appends(
            $request->except('pair_page')
        );

        return Inertia::render('mutual-match-reconciliation/index', [
            'items' => $items,
            'pairIssues' => $pairPaginator,
        ]);
    }
}