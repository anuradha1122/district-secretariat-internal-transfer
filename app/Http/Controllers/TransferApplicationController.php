<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTransferApplicationRequest;
use App\Models\Office;
use App\Models\Service;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\OfficeReviewDecision;

class TransferApplicationController extends Controller
{
    private function authorizeEmployee(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user
                && $user->eligible_employee_id !== null
                && $user->hasRole('Employee')
                && $user->can('view own applications'),
            403
        );
    }

    private function todayInSriLanka(): string
    {
        return Carbon::now('Asia/Colombo')->toDateString();
    }

    public function index(Request $request): Response
    {
        $this->authorizeEmployee($request);

        $today = $this->todayInSriLanka();

        $applications = TransferApplication::query()
            ->where('user_id', $request->user()->id)
            ->with('cycle:id,name')
            ->select([
                'id',
                'transfer_cycle_id',
                'reference_no',
                'status',
                'updated_at',
            ])
            ->addSelect([
                'latest_office_outcome' => OfficeReviewDecision::query()
                    ->select('outcome')
                    ->whereColumn(
                        'office_review_decisions.transfer_application_id',
                        'transfer_applications.id'
                    )
                    ->orderByDesc('id')
                    ->limit(1),

                'latest_office_remarks' => OfficeReviewDecision::query()
                    ->select('remarks')
                    ->whereColumn(
                        'office_review_decisions.transfer_application_id',
                        'transfer_applications.id'
                    )
                    ->orderByDesc('id')
                    ->limit(1),
            ])
                ->orderByDesc('id')
                ->get()
                ->each(function (TransferApplication $application): void {
                    /*
                    * Only send remarks when they are the correction instructions
                    * for an application currently returned to draft.
                    */
                    if (
                        $application->status !== 'draft'
                        || $application->latest_office_outcome !== 'returned'
                    ) {
                        $application->latest_office_remarks = null;
                    }
                });

        $cycles = TransferCycle::query()
            ->where('status', 'open')
            ->whereDate('opens_on', '<=', $today)
            ->whereDate('closes_on', '>=', $today)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('transfer-applications/index', [
            'applications' => $applications,
            'cycles' => $cycles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeEmployee($request);

        abort_unless(
            $request->user()->can('create own applications'),
            403
        );

        $data = $request->validate([
            'transfer_cycle_id' => ['required', 'integer'],
        ]);

        $application = DB::transaction(function () use ($request, $data) {
            /*
             * Lock the cycle while checking its status and dates.
             * This keeps the check and draft creation together.
             */
            $cycle = TransferCycle::query()
                ->whereKey($data['transfer_cycle_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $today = $this->todayInSriLanka();

            if (
                $cycle->status !== 'open'
                || $cycle->opens_on->toDateString() > $today
                || $cycle->closes_on->toDateString() < $today
            ) {
                throw ValidationException::withMessages([
                    'transfer_cycle_id' => 'This transfer cycle is not currently open.',
                ]);
            }

            $existing = TransferApplication::query()
                ->where('transfer_cycle_id', $cycle->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            return TransferApplication::create([
                'transfer_cycle_id' => $cycle->id,
                'user_id' => $request->user()->id,
                'reference_no' => 'TA-'.strtoupper((string) Str::uuid()),
                'status' => 'draft',
            ]);
        });

        if ($application->status !== 'draft') {
            return redirect()->route('transfer-applications.index');
        }

        return redirect()->route(
            'transfer-applications.edit',
            $application
        );
    }

    public function edit(
        Request $request,
        TransferApplication $transferApplication
    ): Response {
        $this->authorizeEmployee($request);

        abort_unless(
            $transferApplication->user_id === $request->user()->id,
            403
        );

        abort_unless($transferApplication->status === 'draft', 403);

        $transferApplication->load([
            'cycle:id,name',
            'children',
            'workHistory',
            'preferences',
        ]);

        return Inertia::render('transfer-applications/form', [
            'application' => [
                'id' => $transferApplication->id,
                'reference_no' => $transferApplication->reference_no,
                'cycle' => $transferApplication->cycle,

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
                'current_office_id' => $transferApplication->current_office_id,
                'service_id' => $transferApplication->service_id,
                'reported_to_current_office_on' => $transferApplication->reported_to_current_office_on?->format('Y-m-d'),
                'accept_nearby_office' => $transferApplication->accept_nearby_office,
                'transfer_reason' => $transferApplication->transfer_reason,

                'children' => $transferApplication->children
                    ->map(fn ($child) => [
                        'name' => $child->name,
                        'age' => $child->age,
                        'school' => $child->school,
                    ]),

                'work_history' => $transferApplication->workHistory
                    ->map(fn ($history) => [
                        'workplace_name' => $history->workplace_name,
                        'from_date' => $history->from_date?->format('Y-m-d'),
                        'to_date' => $history->to_date?->format('Y-m-d'),
                    ]),

                'preferences' => $transferApplication->preferences
                    ->map(fn ($preference) => [
                        'office_id' => $preference->office_id,
                        'reason' => $preference->reason,
                    ]),
            ],

            'offices' => Office::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function update(
        UpdateTransferApplicationRequest $request,
        TransferApplication $transferApplication
    ): RedirectResponse {
        $this->authorizeEmployee($request);

        abort_unless(
            $transferApplication->user_id === $request->user()->id,
            403
        );

        $data = $request->validated();

        DB::transaction(function () use ($request, $transferApplication, $data) {
            $application = TransferApplication::query()
                ->whereKey($transferApplication->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $application->user_id === $request->user()->id,
                403
            );

            abort_unless($application->status === 'draft', 403);

            $application->update(
                Arr::except($data, [
                    'children',
                    'work_history',
                    'preferences',
                ])
            );

            $application->children()->delete();
            $application->workHistory()->delete();
            $application->preferences()->delete();

            $application->children()->createMany($data['children']);

            $application->workHistory()->createMany($data['work_history']);

            foreach ($data['preferences'] as $index => $preference) {
                $application->preferences()->create([
                    'office_id' => $preference['office_id'],
                    'priority' => $index + 1,
                    'reason' => $preference['reason'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('transfer-applications.edit', $transferApplication)
            ->with('success', 'Draft saved successfully.');
    }
}
