<?php

namespace App\Http\Controllers;

use App\Models\OfficeReviewDecision;
use App\Models\OfficeReviewerAssignment;
use App\Models\TransferApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeReviewDecisionController extends Controller
{
    public function store(
        Request $request,
        TransferApplication $transferApplication
    ): RedirectResponse {
        $reviewer = $request->user();

        abort_unless(
            $reviewer
            && $reviewer->hasRole('Office Head')
            && $reviewer->can('review office applications'),
            403
        );

        $data = $request->validate([
            'outcome' => [
                'required',
                Rule::in(['forward', 'returned']),
            ],

            'recommendation' => [
                'nullable',
                Rule::requiredIf(
                    $request->input('outcome') === 'forward'
                ),
                Rule::in(['recommended', 'not_recommended']),
            ],

            'insufficient_service' => ['sometimes', 'boolean'],
            'replacement_required' => ['sometimes', 'boolean'],
            'pending_matters' => ['sometimes', 'boolean'],
            'other_circumstances' => ['sometimes', 'boolean'],

            'signed_paper_verified' => [
                'accepted_if:outcome,forward',
                'boolean',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf(
                    $request->input('outcome') === 'returned'
                ),
            ],
        ], [
            'outcome.required' => 'Please select an action.',
            'recommendation.required' => 'Please select a recommendation.',
            'signed_paper_verified.required' => 'Please confirm that you checked the signed paper application.',
            'signed_paper_verified.accepted_if' => 'Please confirm that you checked the signed paper application.',
            'remarks.required' => 'Please explain what the employee needs to correct.',
        ]);

        if (
            $data['outcome'] === 'returned'
            && trim((string) ($data['remarks'] ?? '')) === ''
        ) {
            throw ValidationException::withMessages([
                'remarks' => 'Please explain what the employee needs to correct.',
            ]);
        }

        DB::transaction(function () use (
            $request,
            $transferApplication,
            $data,
            $reviewer
        ): void {
            /*
             * Lock the application so two reviewers cannot both
             * record a decision for the same submission.
             */
            $application = TransferApplication::query()
                ->whereKey($transferApplication->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $application->user_id === $reviewer->id,
                403,
                'You cannot review your own application.'
            );

            /*
             * Recheck scope inside the transaction.
             * Lock the assignment while making the decision.
             */
            $assignment = OfficeReviewerAssignment::query()
                ->where('user_id', $reviewer->id)
                ->where('office_id', $application->current_office_id)
                ->where('is_active', true)
                ->whereHas('office', function ($query): void {
                    $query->where('is_active', true);
                })
                ->lockForUpdate()
                ->first();

            abort_unless($assignment, 403);

            if ($application->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'outcome' => 'This application is no longer awaiting office review. Refresh the page to see its current status.',
                ]);
            }

            $application->load([
                'cycle',
                'currentOffice',
                'service',
                'children',
                'workHistory',
                'preferences.office',
            ]);

            /*
             * Capture the data before returning it for changes.
             * This snapshot is not a replacement for retaining the
             * physical signed paper application.
             */
            $snapshot = [
                'application' => $application->attributesToArray(),
                'cycle' => $application->cycle?->toArray(),
                'current_office' => $application->currentOffice?->toArray(),
                'service' => $application->service?->toArray(),
                'children' => $application->children->toArray(),
                'work_history' => $application->workHistory->toArray(),
                'preferences' => $application->preferences->toArray(),
            ];

            $isForward = $data['outcome'] === 'forward';

            OfficeReviewDecision::create([
                'transfer_application_id' => $application->id,
                'reviewer_id' => $reviewer->id,
                'office_id' => $application->current_office_id,

                'outcome' => $data['outcome'],

                'recommendation' => $isForward
                    ? $data['recommendation']
                    : null,

                'insufficient_service' => $isForward
                    ? ($data['insufficient_service'] ?? false)
                    : false,

                'replacement_required' => $isForward
                    ? ($data['replacement_required'] ?? false)
                    : false,

                'pending_matters' => $isForward
                    ? ($data['pending_matters'] ?? false)
                    : false,

                'other_circumstances' => $isForward
                    ? ($data['other_circumstances'] ?? false)
                    : false,

                'signed_paper_verified' => $isForward
                    ? true
                    : false,

                'remarks' => isset($data['remarks'])
                    ? trim($data['remarks'])
                    : null,

                'application_snapshot' => $snapshot,
                'decided_at' => now(),
            ]);

            if ($isForward) {
                $application->status = 'office_recommended';
            } else {
                /*
                 * Returning to draft enables the existing employee
                 * editor. The employee must accept the declaration
                 * and submit again.
                 */
                $application->status = 'draft';
                $application->declaration_accepted = false;
                $application->submitted_at = null;
            }

            $application->save();
        });

        return redirect()
            ->route('office-reviews.index')
            ->with(
                'success',
                $data['outcome'] === 'forward'
                    ? 'Recommendation recorded and application forwarded for district review.'
                    : 'Application returned to the employee for correction.'
            );
    }
}
