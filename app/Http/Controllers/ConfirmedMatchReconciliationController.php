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

        /*
         * Find confirmed decisions with at least one participant whose
         * linked eligible employee record is missing or inactive.
         */
        $decisions = DB::table('mutual_match_decisions as decisions')
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
                    ->orWhere('eligible.is_active', false);
            })
            ->orderByDesc('decisions.id')
            ->select([
                'decisions.id as decision_id',
                'decisions.mutual_match_run_id as run_id',
                'decisions.decided_at',
                'cycles.name as cycle_name',
                'participants.user_id',
                'applications.reference_no',
                'applications.full_name',
                'eligible.id as eligible_employee_id',
                'eligible.is_active',
            ])
            ->paginate(20)
            ->through(fn ($item) => [
                'decision_id' => $item->decision_id,
                'run_id' => $item->run_id,
                'decided_at' => $item->decided_at,
                'cycle_name' => $item->cycle_name,
                'user_id' => $item->user_id,
                'reference_no' => $item->reference_no,
                'full_name' => $item->full_name,
                'issue' => $item->eligible_employee_id === null
                    ? 'Eligibility record missing'
                    : 'Employee eligibility is inactive',
            ]);

        return Inertia::render('mutual-match-reconciliation/index', [
            'items' => $decisions,
        ]);
    }
}
