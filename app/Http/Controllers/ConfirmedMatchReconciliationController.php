<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmedMatchReconciliationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->can('view mutual matches'),
            403
        );

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
            ->paginate(20)
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

                    /*
                     * Keep the existing page's issue field.
                     */
                    'issue' => implode('; ', $issues),
                ];
            });

        return Inertia::render('mutual-match-reconciliation/index', [
            'items' => $items,
        ]);
    }
}