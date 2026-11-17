<?php

namespace App\Http\Controllers;

use App\Models\DistrictReviewDecision;
use App\Models\OfficeReviewDecision;
use App\Models\TransferApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Notifications\EmployeeNotificationService;

class DistrictReviewDecisionController extends Controller
{
    private function authorizeViewer(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->hasRole('District Approver')
            && $user->can('view district applications'),
            403
        );
    }

    private function authorizeDecisionMaker(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->hasRole('District Approver')
            && $user->can('review district applications'),
            403
        );
    }

    private function latestForwardedOfficeDecision(
        TransferApplication $application
    ): OfficeReviewDecision {
        $decision = OfficeReviewDecision::query()
            ->where('transfer_application_id', $application->id)
            ->latest('id')
            ->firstOrFail();

        abort_unless($decision->outcome === 'forward', 404);

        return $decision;
    }

    private function authorizeApplication(
        Request $request,
        TransferApplication $application,
        OfficeReviewDecision $officeDecision
    ): void {
        /*
         * Do not expose an application outside the district inbox scope.
         */
        abort_unless($application->status === 'office_recommended', 404);

        abort_if(
            $application->user_id === $request->user()->id,
            404
        );

        /*
         * The Office Head and district decision maker must be different.
         */
        abort_if(
            $officeDecision->reviewer_id === $request->user()->id,
            404
        );
    }

    public function show(
        Request $request,
        TransferApplication $transferApplication
    ): Response {
        $this->authorizeViewer($request);

        $officeDecision = $this->latestForwardedOfficeDecision(
            $transferApplication
        );

        $this->authorizeApplication(
            $request,
            $transferApplication,
            $officeDecision
        );

        $transferApplication->load([
            'cycle:id,name',
            'currentOffice:id,code,name',
            'service:id,code,name',
            'children',
            'workHistory',
            'preferences.office:id,code,name',
        ]);

        $officeDecision->load('reviewer:id,name');

        return Inertia::render('district-reviews/show', [
            'application' => [
                'id' => $transferApplication->id,
                'reference_no' => $transferApplication->reference_no,
                'status' => $transferApplication->status,
                'submitted_at' => $transferApplication->submitted_at?->toDateTimeString(),

                'full_name' => $transferApplication->full_name,
                'name_with_initials_sinhala' => $transferApplication->name_with_initials_sinhala,
                'name_with_initials_english' => $transferApplication->name_with_initials_english,
                'date_of_birth' => $transferApplication->date_of_birth?->format('Y-m-d'),
                'gender' => $transferApplication->gender,

                'permanent_address' => $transferApplication->permanent_address,
                'temporary_address' => $transferApplication->temporary_address,
                'office_phone' => $transferApplication->office_phone,
                'personal_phone' => $transferApplication->personal_phone,

                'marital_status' => $transferApplication->marital_status,
                'spouse_name' => $transferApplication->spouse_name,
                'spouse_occupation' => $transferApplication->spouse_occupation,
                'spouse_workplace' => $transferApplication->spouse_workplace,

                'post' => $transferApplication->post,
                'grade' => $transferApplication->grade,
                'appointment_letter_no' => $transferApplication->appointment_letter_no,
                'is_confirmed' => $transferApplication->is_confirmed,
                'reported_to_current_office_on' => $transferApplication->reported_to_current_office_on?->format('Y-m-d'),

                'accept_nearby_office' => $transferApplication->accept_nearby_office,
                'transfer_reason' => $transferApplication->transfer_reason,

                'current_office' => $transferApplication->currentOffice,
                'service' => $transferApplication->service,
                'cycle' => $transferApplication->cycle,

                'children' => $transferApplication->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'age' => $child->age,
                    'school' => $child->school,
                ]),

                'work_history' => $transferApplication->workHistory->map(fn ($history) => [
                    'id' => $history->id,
                    'workplace_name' => $history->workplace_name,
                    'from_date' => $history->from_date
                        ? (string) $history->from_date
                        : null,
                    'to_date' => $history->to_date
                        ? (string) $history->to_date
                        : null,
                ]),

                'preferences' => $transferApplication->preferences
                    ->sortBy('priority')
                    ->values()
                    ->map(fn ($preference) => [
                        'id' => $preference->id,
                        'priority' => $preference->priority,
                        'reason' => $preference->reason,
                        'office' => $preference->office,
                    ]),
            ],

            'officeDecision' => [
                'id' => $officeDecision->id,
                'recommendation' => $officeDecision->recommendation,
                'insufficient_service' => $officeDecision->insufficient_service,
                'replacement_required' => $officeDecision->replacement_required,
                'pending_matters' => $officeDecision->pending_matters,
                'other_circumstances' => $officeDecision->other_circumstances,
                'signed_paper_verified' => $officeDecision->signed_paper_verified,
                'remarks' => $officeDecision->remarks,
                'decided_at' => $officeDecision->decided_at?->toDateTimeString(),
                'reviewer' => $officeDecision->reviewer,
            ],

            'canDecide' => $request->user()->can(
                'review district applications'
            ),
        ]);
    }

    public function store(
        Request $request,
        TransferApplication $transferApplication
    ): RedirectResponse {
        $this->authorizeDecisionMaker($request);

        $data = $request->validate([
            'office_review_decision_id' => [
                'required',
                'integer',
            ],

            'outcome' => [
                'required',
                Rule::in(['approved', 'rejected']),
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
            'outcome.required' => 'Please select a decision.',
            'remarks.required' => 'Please provide a reason for rejection.',
        ]);

        if (
            $data['outcome'] === 'rejected'
            && trim((string) ($data['remarks'] ?? '')) === ''
        ) {
            throw ValidationException::withMessages([
                'remarks' => 'Please provide a reason for rejection.',
            ]);
        }

        DB::transaction(function () use (
            $request,
            $transferApplication,
            $data
        ): void {
            $application = TransferApplication::query()
                ->whereKey($transferApplication->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($application->status !== 'office_recommended') {
                throw ValidationException::withMessages([
                    'outcome' => 'This application is no longer awaiting district review.',
                ]);
            }

            $officeDecision = OfficeReviewDecision::query()
                ->where('transfer_application_id', $application->id)
                ->latest('id')
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($officeDecision->outcome === 'forward', 404);

            abort_if(
                $application->user_id === $request->user()->id,
                403,
                'You cannot review your own application.'
            );

            abort_if(
                $officeDecision->reviewer_id === $request->user()->id,
                403,
                'The Office Head and district reviewer must be different.'
            );

            if (
                (int) $data['office_review_decision_id']
                !== (int) $officeDecision->id
            ) {
                throw ValidationException::withMessages([
                    'outcome' => 'The office recommendation has changed. Refresh the page before making a decision.',
                ]);
            }

            DistrictReviewDecision::create([
                'transfer_application_id' => $application->id,
                'office_review_decision_id' => $officeDecision->id,
                'reviewer_id' => $request->user()->id,
                'outcome' => $data['outcome'],
                'remarks' => isset($data['remarks'])
                    ? trim($data['remarks'])
                    : null,
                'decided_at' => now(),
            ]);

            $application->status = $data['outcome'];
            $application->save();

            app(EmployeeNotificationService::class)->record(
                $application,
                $data['outcome'] === 'approved'
                    ? 'district_approved'
                    : 'district_rejected'
            );
        });

        return redirect()
            ->route('district-reviews.index')
            ->with('success', 'District decision recorded successfully.');
    }
}
