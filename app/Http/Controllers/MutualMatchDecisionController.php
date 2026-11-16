<?php

namespace App\Http\Controllers;

use App\Models\MutualMatchDecision;
use App\Models\MutualMatchRun;
use App\Models\TransferApplication;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MutualMatchDecisionController extends Controller
{
    public function store(
        Request $request,
        MutualMatchRun $mutualMatchRun
    ): RedirectResponse {
        abort_unless(
            $request->user()?->can('manage mutual matches'),
            403
        );

        $data = $request->validate([
            'application_a_id' => ['required', 'integer', 'min:1'],
            'application_b_id' => ['required', 'integer', 'min:1', 'different:application_a_id'],

            'outcome' => [
                'required',
                Rule::in(['confirmed', 'rejected']),
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf(
                    $request->input('outcome') === 'rejected'
                ),
            ],
        ], [
            'remarks.required' => 'Please provide a reason for rejecting this pair.',
        ]);

        $remarks = trim((string) ($data['remarks'] ?? ''));

        if ($data['outcome'] === 'rejected' && $remarks === '') {
            throw ValidationException::withMessages([
                'remarks' => 'Please provide a reason for rejecting this pair.',
            ]);
        }

        $idA = (int) $data['application_a_id'];
        $idB = (int) $data['application_b_id'];

        /*
         * Candidate snapshots use ascending application IDs.
         */
        if ($idA >= $idB) {
            throw ValidationException::withMessages([
                'pair' => 'The selected pair is invalid. Refresh the page and try again.',
            ]);
        }

        try {
            DB::transaction(function () use (
                $request,
                $mutualMatchRun,
                $data,
                $remarks,
                $idA,
                $idB
            ): void {
                /*
                 * Serialize decisions made against the same run.
                 */
                $run = MutualMatchRun::query()
                    ->whereKey($mutualMatchRun->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $candidate = collect($run->candidate_snapshot)
                    ->first(function (array $item) use ($idA, $idB): bool {
                        return (int) ($item['application_a']['id'] ?? 0) === $idA
                            && (int) ($item['application_b']['id'] ?? 0) === $idB;
                    });

                if (! $candidate) {
                    throw ValidationException::withMessages([
                        'pair' => 'This pair was not part of the saved matching run.',
                    ]);
                }

                $alreadyDecided = MutualMatchDecision::query()
                    ->where('mutual_match_run_id', $run->id)
                    ->where('application_a_id', $idA)
                    ->where('application_b_id', $idB)
                    ->exists();

                if ($alreadyDecided) {
                    throw ValidationException::withMessages([
                        'pair' => 'A decision has already been recorded for this pair in this run.',
                    ]);
                }

                /*
                 * Lock in a stable order. Concurrent confirmations sharing
                 * an application must wait for the first transaction.
                 */
                $applications = TransferApplication::query()
                    ->whereIn('id', [$idA, $idB])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $applicationA = $applications->get($idA);
                $applicationB = $applications->get($idB);

                if (! $applicationA || ! $applicationB) {
                    throw ValidationException::withMessages([
                        'pair' => 'An application is no longer available.',
                    ]);
                }

                /*
                 * A reviewer must not decide on a pair containing their
                 * own application.
                 */
                if (
                    (int) $applicationA->user_id === (int) $request->user()->id
                    || (int) $applicationB->user_id === (int) $request->user()->id
                ) {
                    abort(403, 'You cannot decide on your own application.');
                }

                if ($data['outcome'] === 'confirmed') {
                    $this->validateCurrentPair(
                        $run,
                        $candidate,
                        $applicationA,
                        $applicationB
                    );

                    $alreadyConfirmed = DB::table('mutual_match_participants')
                        ->where('transfer_cycle_id', $run->transfer_cycle_id)
                        ->whereIn('user_id', [
                            $applicationA->user_id,
                            $applicationB->user_id,
                        ])
                        ->exists();

                    if ($alreadyConfirmed) {
                        throw ValidationException::withMessages([
                            'pair' => 'At least one employee is already confirmed in another pair for this cycle.',
                        ]);
                    }
                }

                $decision = MutualMatchDecision::create([
                    'mutual_match_run_id' => $run->id,
                    'transfer_cycle_id' => $run->transfer_cycle_id,
                    'application_a_id' => $idA,
                    'application_b_id' => $idB,
                    'reviewer_id' => $request->user()->id,
                    'outcome' => $data['outcome'],
                    'remarks' => $remarks !== '' ? $remarks : null,
                    'decided_at' => now(),
                ]);

                if ($data['outcome'] === 'confirmed') {
                    $timestamp = now();

                    DB::table('mutual_match_participants')->insert([
                        [
                            'mutual_match_decision_id' => $decision->id,
                            'transfer_cycle_id' => $run->transfer_cycle_id,
                            'user_id' => $applicationA->user_id,
                            'transfer_application_id' => $applicationA->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ],
                        [
                            'mutual_match_decision_id' => $decision->id,
                            'transfer_cycle_id' => $run->transfer_cycle_id,
                            'user_id' => $applicationB->user_id,
                            'transfer_application_id' => $applicationB->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ],
                    ]);
                }
            }, 3);
        } catch (QueryException $exception) {
            /*
             * The unique participant constraint is the final protection
             * against concurrent overlapping confirmations.
             *
             * Only convert duplicate-key errors. Other database problems
             * must remain visible for diagnosis.
             */
            $sqlState = (string) ($exception->errorInfo[0] ?? '');

            $driverCode = (int) ($exception->errorInfo[1] ?? 0);

            $isDuplicate = (
                $sqlState === '23000'
                && $driverCode === 1062
            ) || (
                $sqlState === '23000'
                && $driverCode === 19
            );

            if ($isDuplicate) {
                throw ValidationException::withMessages([
                    'pair' => 'The pair or one of its employees has already been confirmed or reviewed. Refresh the page.',
                ]);
            }

            throw $exception;
        }

        return redirect()
            ->route('mutual-match-runs.show', $mutualMatchRun)
            ->with('success', 'Pair decision recorded successfully.');
    }

    private function validateCurrentPair(
        MutualMatchRun $run,
        array $candidate,
        TransferApplication $applicationA,
        TransferApplication $applicationB
    ): void {
        $stale = function (): never {
            throw ValidationException::withMessages([
                'pair' => 'This saved pair is no longer current or eligible. Generate a new matching run.',
            ]);
        };

        if ($run->algorithm_version !== 'reciprocal-greedy-v1') {
            $stale();
        }

        if (
            $applicationA->status !== 'approved'
            || $applicationB->status !== 'approved'
            || (int) $applicationA->transfer_cycle_id !== (int) $run->transfer_cycle_id
            || (int) $applicationB->transfer_cycle_id !== (int) $run->transfer_cycle_id
            || (int) $applicationA->service_id !== (int) $run->service_id
            || (int) $applicationB->service_id !== (int) $run->service_id
            || (int) $applicationA->user_id === (int) $applicationB->user_id
            || ! $applicationA->current_office_id
            || ! $applicationB->current_office_id
            || (int) $applicationA->current_office_id
                === (int) $applicationB->current_office_id
        ) {
            $stale();
        }

        /*
         * Verify the current offices match the offices in the saved pair.
         */
        if (
            (int) $applicationA->current_office_id
                !== (int) ($candidate['application_a']['office']['id'] ?? 0)
            || (int) $applicationB->current_office_id
                !== (int) ($candidate['application_b']['office']['id'] ?? 0)
        ) {
            $stale();
        }

        /*
         * Lock linked eligible-employee records while checking active status.
         */
        $userIds = [
            (int) $applicationA->user_id,
            (int) $applicationB->user_id,
        ];

        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'eligible_employee_id'])
            ->keyBy('id');

        $eligibleA = $users->get($applicationA->user_id)?->eligible_employee_id;
        $eligibleB = $users->get($applicationB->user_id)?->eligible_employee_id;

        if (! $eligibleA || ! $eligibleB || $eligibleA === $eligibleB) {
            $stale();
        }

        $eligibleEmployees = DB::table('eligible_employees')
            ->whereIn('id', [$eligibleA, $eligibleB])
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'is_active'])
            ->keyBy('id');

        if (
            ! $eligibleEmployees->get($eligibleA)?->is_active
            || ! $eligibleEmployees->get($eligibleB)?->is_active
        ) {
            $stale();
        }

        /*
         * Lock the current preference rows and verify both reciprocal
         * preferences and their saved ranks.
         */
        $preferences = DB::table('transfer_application_preferences')
            ->whereIn('transfer_application_id', [
                $applicationA->id,
                $applicationB->id,
            ])
            ->orderBy('transfer_application_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $preferenceA = $preferences->first(
            fn ($preference) =>
                (int) $preference->transfer_application_id === (int) $applicationA->id
                && (int) $preference->office_id === (int) $applicationB->current_office_id
        );

        $preferenceB = $preferences->first(
            fn ($preference) =>
                (int) $preference->transfer_application_id === (int) $applicationB->id
                && (int) $preference->office_id === (int) $applicationA->current_office_id
        );

        if (
            ! $preferenceA
            || ! $preferenceB
            || (int) $preferenceA->priority !== (int) ($candidate['rank_a'] ?? 0)
            || (int) $preferenceB->priority !== (int) ($candidate['rank_b'] ?? 0)
        ) {
            $stale();
        }
    }
}
