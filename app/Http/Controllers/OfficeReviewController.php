<?php

namespace App\Http\Controllers;

use App\Models\OfficeReviewerAssignment;
use App\Models\TransferApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeReviewController extends Controller
{
    private function authorizeReviewer(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->hasRole('Office Head')
            && $user->can('view office applications'),
            403
        );
    }

    /**
     * Restrict applications to the reviewer's active assignments.
     */
    private function scopedApplications(Request $request): Builder
    {
        $reviewerId = $request->user()->id;

        $assignedOfficeIds = OfficeReviewerAssignment::query()
            ->where('user_id', $reviewerId)
            ->where('is_active', true)
            ->whereHas('office', function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->select('office_id');

        return TransferApplication::query()
            ->where('status', 'submitted')
            ->where('user_id', '!=', $reviewerId)
            ->whereIn('current_office_id', $assignedOfficeIds);
    }

    public function index(Request $request): Response
    {
        $this->authorizeReviewer($request);

        $applications = $this->scopedApplications($request)
            ->with([
                'currentOffice:id,code,name',
                'service:id,code,name',
                'cycle:id,name',
            ])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (TransferApplication $application) => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'full_name' => $application->full_name,
                'post' => $application->post,
                'grade' => $application->grade,
                'submitted_at' => $application->submitted_at?->toDateTimeString(),
                'current_office' => $application->currentOffice,
                'service' => $application->service,
                'cycle' => $application->cycle,
            ]);

        return Inertia::render('office-reviews/index', [
            'applications' => $applications,
        ]);
    }

    public function show(
        Request $request,
        TransferApplication $transferApplication
    ): Response {
        $this->authorizeReviewer($request);

        /*
         * Use the same scope for direct URLs as for the inbox.
         * An application outside the scope returns 404.
         */
        $application = $this->scopedApplications($request)
            ->with([
                'currentOffice:id,code,name',
                'service:id,code,name',
                'cycle:id,name',
                'children',
                'workHistory',
                'preferences.office:id,code,name',
            ])
            ->findOrFail($transferApplication->id);

        return Inertia::render('office-reviews/show', [
            'application' => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'status' => $application->status,
                'submitted_at' => $application->submitted_at?->toDateTimeString(),

                'full_name' => $application->full_name,
                'name_with_initials_sinhala' => $application->name_with_initials_sinhala,
                'name_with_initials_english' => $application->name_with_initials_english,
                'date_of_birth' => $application->date_of_birth?->format('Y-m-d'),
                'gender' => $application->gender,

                'permanent_address' => $application->permanent_address,
                'temporary_address' => $application->temporary_address,
                'office_phone' => $application->office_phone,
                'personal_phone' => $application->personal_phone,

                'marital_status' => $application->marital_status,
                'spouse_name' => $application->spouse_name,
                'spouse_occupation' => $application->spouse_occupation,
                'spouse_workplace' => $application->spouse_workplace,

                'post' => $application->post,
                'grade' => $application->grade,
                'appointment_letter_no' => $application->appointment_letter_no,
                'is_confirmed' => $application->is_confirmed,
                'reported_to_current_office_on' => $application->reported_to_current_office_on?->format('Y-m-d'),

                'accept_nearby_office' => $application->accept_nearby_office,
                'transfer_reason' => $application->transfer_reason,

                'current_office' => $application->currentOffice,
                'service' => $application->service,
                'cycle' => $application->cycle,

                'children' => $application->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'age' => $child->age,
                    'school' => $child->school,
                ]),

                'work_history' => $application->workHistory->map(fn ($history) => [
                    'id' => $history->id,
                    'workplace_name' => $history->workplace_name,
                    'from_date' => $history->from_date
                        ? (string) $history->from_date
                        : null,
                    'to_date' => $history->to_date
                        ? (string) $history->to_date
                        : null,
                ]),

                'preferences' => $application->preferences
                    ->sortBy('priority')
                    ->values()
                    ->map(fn ($preference) => [
                        'id' => $preference->id,
                        'priority' => $preference->priority,
                        'reason' => $preference->reason,
                        'office' => $preference->office,
                    ]),
            ],
        ]);
    }
}
