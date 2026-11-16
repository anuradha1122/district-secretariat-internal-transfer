<?php

namespace App\Http\Controllers;

use App\Models\MutualMatchRun;
use App\Models\TransferApplication;
use App\Services\Transfers\MutualTransferPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\MutualMatchDecision;

class MutualMatchRunController extends Controller
{
    private const ALGORITHM_VERSION = 'reciprocal-greedy-v1';

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()?->can('view mutual matches'),
            403
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()?->can('manage mutual matches'),
            403
        );
    }

    public function index(Request $request): Response
    {
        $this->authorizeView($request);

        $runs = MutualMatchRun::query()
            ->with([
                'cycle:id,name',
                'service:id,code,name',
                'creator:id,name',
            ])
            ->latest('id')
            ->paginate(20)
            ->through(fn (MutualMatchRun $run) => [
                'id' => $run->id,
                'algorithm_version' => $run->algorithm_version,
                'input_count' => $run->input_count,
                'candidate_count' => $run->candidate_count,
                'generated_at' => $run->generated_at?->toDateTimeString(),
                'cycle' => $run->cycle,
                'service' => $run->service,
                'creator' => $run->creator,
            ]);

        return Inertia::render('mutual-match-runs/index', [
            'runs' => $runs,
        ]);
    }

    public function store(
        Request $request,
        MutualTransferPreviewService $previewService
    ): RedirectResponse {
        $this->authorizeManage($request);

        $data = $request->validate([
            'cycle_id' => [
                'required',
                'integer',
                Rule::exists('transfer_cycles', 'id'),
            ],

            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id'),
            ],
        ]);

        /*
         * Generate from the server's current data. Do not accept candidate
         * results supplied by the browser.
         */
        $run = DB::transaction(function () use (
            $request,
            $previewService,
            $data
        ): MutualMatchRun {
            $applications = $previewService->eligibleApplications(
                (int) $data['cycle_id'],
                (int) $data['service_id']
            );

            $inputs = $applications
                ->map(function (TransferApplication $application): array {
                    return [
                        'application_id' => $application->id,
                        'user_id' => $application->user_id,
                        'reference_no' => $application->reference_no,
                        'name' => $application->full_name,

                        'cycle_id' => $application->transfer_cycle_id,
                        'service_id' => $application->service_id,
                        'status' => $application->status,

                        'current_office_id' => $application->current_office_id,
                        'current_office' => $application->currentOffice
                            ? [
                                'id' => $application->currentOffice->id,
                                'code' => $application->currentOffice->code,
                                'name' => $application->currentOffice->name,
                            ]
                            : null,

                        'post' => $application->post,
                        'grade' => $application->grade,

                        'submitted_at' => $application->submitted_at?->toISOString(),

                        'preferences' => $application->preferences
                            ->sortBy('priority')
                            ->values()
                            ->map(fn ($preference) => [
                                'office_id' => $preference->office_id,
                                'priority' => $preference->priority,
                            ])
                            ->all(),
                    ];
                })
                ->values()
                ->all();

            $candidates = $previewService->buildCandidates($applications);

            return MutualMatchRun::create([
                'transfer_cycle_id' => (int) $data['cycle_id'],
                'service_id' => (int) $data['service_id'],
                'created_by' => $request->user()->id,
                'algorithm_version' => self::ALGORITHM_VERSION,
                'input_count' => count($inputs),
                'candidate_count' => count($candidates),
                'input_snapshot' => $inputs,
                'candidate_snapshot' => $candidates,
                'generated_at' => now(),
            ]);
        });

        return redirect()
            ->route('mutual-match-runs.show', $run)
            ->with('success', 'Matching run saved successfully.');
    }

    public function show(
        Request $request,
        MutualMatchRun $mutualMatchRun
    ): Response {
        $this->authorizeView($request);

        $mutualMatchRun->load([
            'cycle:id,name',
            'service:id,code,name',
            'creator:id,name',
        ]);

        return Inertia::render('mutual-match-runs/show', [
            'run' => [
                'id' => $mutualMatchRun->id,
                'algorithm_version' => $mutualMatchRun->algorithm_version,
                'input_count' => $mutualMatchRun->input_count,
                'candidate_count' => $mutualMatchRun->candidate_count,
                'generated_at' => $mutualMatchRun->generated_at?->toDateTimeString(),
                'cycle' => $mutualMatchRun->cycle,
                'service' => $mutualMatchRun->service,
                'creator' => $mutualMatchRun->creator,

                /*
                 * The input snapshot remains in the database for audit.
                 * Only the candidate results are sent to this page.
                 */
                'candidates' => $mutualMatchRun->candidate_snapshot,

                'decisions' => MutualMatchDecision::query()
                    ->where('mutual_match_run_id', $mutualMatchRun->id)
                    ->with('reviewer:id,name')
                    ->orderBy('id')
                    ->get()
                    ->map(fn (MutualMatchDecision $decision) => [
                        'id' => $decision->id,
                        'application_a_id' => $decision->application_a_id,
                        'application_b_id' => $decision->application_b_id,
                        'outcome' => $decision->outcome,
                        'remarks' => $decision->remarks,
                        'decided_at' => $decision->decided_at?->toDateTimeString(),
                        'reviewer' => $decision->reviewer,
                    ]),
            ],

            'canManage' => $request->user()->can('manage mutual matches'),
        ]);
    }
}
