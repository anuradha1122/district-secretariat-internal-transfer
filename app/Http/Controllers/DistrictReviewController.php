<?php

namespace App\Http\Controllers;

use App\Models\OfficeReviewDecision;
use App\Models\TransferApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DistrictReviewController extends Controller
{
    private function authorizeDistrictViewer(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->hasRole('District Approver')
            && $user->can('view district applications'),
            403
        );
    }

    public function index(Request $request): Response
    {
        $this->authorizeDistrictViewer($request);

        $reviewerId = $request->user()->id;

        /*
         * Select the latest office decision for each application.
         * Both positive and negative recommendations are included.
         */
        $latestDecisionId = OfficeReviewDecision::query()
            ->select('id')
            ->whereColumn(
                'office_review_decisions.transfer_application_id',
                'transfer_applications.id'
            )
            ->orderByDesc('id')
            ->limit(1);

        $applications = TransferApplication::query()
            ->where('status', 'office_recommended')

            /*
             * A district reviewer must not review their own application.
             */
            ->where('user_id', '!=', $reviewerId)

            /*
             * Keep the office and district review stages separate.
             * The officer who made the latest office decision does not
             * see that application in their own district inbox.
             */
            ->whereExists(function ($query) use ($latestDecisionId, $reviewerId): void {
                $query
                    ->selectRaw('1')
                    ->from('office_review_decisions as district_office_decision')
                    ->whereColumn(
                        'district_office_decision.transfer_application_id',
                        'transfer_applications.id'
                    )
                    ->where('district_office_decision.outcome', 'forward')
                    ->where('district_office_decision.reviewer_id', '!=', $reviewerId)
                    ->where(
                        'district_office_decision.id',
                        '=',
                        $latestDecisionId
                    );
            })
            ->with([
                'currentOffice:id,code,name',
                'service:id,code,name',
                'cycle:id,name',
            ])
            ->select('transfer_applications.*')
            ->addSelect([
                'office_recommendation' => OfficeReviewDecision::query()
                    ->select('recommendation')
                    ->whereColumn(
                        'office_review_decisions.transfer_application_id',
                        'transfer_applications.id'
                    )
                    ->orderByDesc('id')
                    ->limit(1),

                'office_decided_at' => OfficeReviewDecision::query()
                    ->select('decided_at')
                    ->whereColumn(
                        'office_review_decisions.transfer_application_id',
                        'transfer_applications.id'
                    )
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(20)
            ->through(fn (TransferApplication $application) => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'full_name' => $application->full_name,
                'post' => $application->post,
                'grade' => $application->grade,
                'submitted_at' => $application->submitted_at?->toDateTimeString(),
                'office_recommendation' => $application->office_recommendation,
                'office_decided_at' => $application->office_decided_at,
                'current_office' => $application->currentOffice,
                'service' => $application->service,
                'cycle' => $application->cycle,
            ]);

        return Inertia::render('district-reviews/index', [
            'applications' => $applications,
        ]);
    }
}
