<?php

namespace App\Http\Controllers;

use App\Models\EligibleEmployee;
use App\Models\TransferApplication;
use App\Models\TransferCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TransferApplicationSubmissionController extends Controller
{
    private function authorizeOwner(
        Request $request,
        TransferApplication $application
    ): void {
        $user = $request->user();

        abort_unless(
            $user
                && $user->eligible_employee_id !== null
                && $user->hasRole('Employee')
                && $user->can('view own applications')
                && $application->user_id === $user->id,
            403
        );
    }

    public function review(
        Request $request,
        TransferApplication $transferApplication
    ): Response {
        $this->authorizeOwner($request, $transferApplication);

        $transferApplication->load([
            'cycle:id,name',
            'currentOffice:id,name,code',
            'service:id,name,code',
            'children',
            'workHistory',
            'preferences.office:id,name,code',
        ]);

        return Inertia::render('transfer-applications/review', [
            'application' => $transferApplication,
        ]);
    }

    public function submit(
        Request $request,
        TransferApplication $transferApplication
    ): RedirectResponse {
        $this->authorizeOwner($request, $transferApplication);

        abort_unless(
            $request->user()->can('create own applications'),
            403
        );

        $request->validate([
            'declaration_accepted' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $transferApplication) {
            /*
             * Lock the cycle first, followed by the application.
             * This allows the cycle status to be checked during
             * the same transaction as submission.
             */
            $cycle = TransferCycle::query()
                ->whereKey($transferApplication->transfer_cycle_id)
                ->lockForUpdate()
                ->firstOrFail();

            $application = TransferApplication::query()
                ->whereKey($transferApplication->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeOwner($request, $application);

            if ($application->status !== 'draft') {
                throw ValidationException::withMessages([
                    'application' => 'This application has already been submitted or cannot be submitted.',
                ]);
            }

            $today = Carbon::now('Asia/Colombo')->toDateString();

            if (
                $cycle->status !== 'open'
                || $cycle->opens_on->toDateString() > $today
                || $cycle->closes_on->toDateString() < $today
            ) {
                throw ValidationException::withMessages([
                    'application' => 'The transfer cycle is not currently open for submissions.',
                ]);
            }

            $application->load([
                'currentOffice',
                'service',
                'preferences.office',
            ]);

            $validator = Validator::make(
                $application->toArray(),
                [
                    'full_name' => ['required', 'string', 'max:255'],

                    'name_with_initials_english' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'date_of_birth' => [
                        'required',
                        'date',
                        'before_or_equal:today',
                    ],

                    'gender' => [
                        'required',
                        'in:female,male',
                    ],

                    'permanent_address' => [
                        'required',
                        'string',
                    ],

                    'personal_phone' => [
                        'required',
                        'string',
                        'max:20',
                    ],

                    'marital_status' => [
                        'required',
                        'in:married,unmarried',
                    ],

                    'post' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'grade' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'appointment_letter_no' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'is_confirmed' => [
                        'required',
                        'boolean',
                    ],

                    'current_office_id' => [
                        'required',
                        'integer',
                    ],

                    'service_id' => [
                        'required',
                        'integer',
                    ],

                    'reported_to_current_office_on' => [
                        'required',
                        'date',
                        'before_or_equal:today',
                    ],

                    'accept_nearby_office' => [
                        'required',
                        'boolean',
                    ],

                    'transfer_reason' => [
                        'required',
                        'string',
                    ],
                ]
            );

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'application' => implode(' ', $validator->errors()->all()),
                ]);
            }

            if (
                ! $application->currentOffice
                || ! $application->currentOffice->is_active
                || ! $application->service
                || ! $application->service->is_active
            ) {
                throw ValidationException::withMessages([
                    'application' => 'Your current office or service is no longer active. Please update your draft.',
                ]);
            }

            if (
                $application->preferences->count() < 1
                || $application->preferences->count() > 5
            ) {
                throw ValidationException::withMessages([
                    'application' => 'Select between one and five preferred offices.',
                ]);
            }

            foreach ($application->preferences as $preference) {
                if (
                    ! $preference->office
                    || ! $preference->office->is_active
                    || $preference->office_id === $application->current_office_id
                ) {
                    throw ValidationException::withMessages([
                        'application' => 'Check your preferred offices. They must be active and different from your current office.',
                    ]);
                }
            }

            $application->update([
                'declaration_accepted' => true,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        return redirect()
            ->route(
                'transfer-applications.review',
                $transferApplication
            )
            ->with('success', 'Application submitted successfully.');
    }

    public function pdf(
        Request $request,
        TransferApplication $transferApplication
    ): HttpResponse {
        $this->authorizeOwner($request, $transferApplication);

        abort_unless(
            in_array(
                $transferApplication->status,
                ['submitted', 'office_recommended', 'approved', 'rejected'],
                true
            ),
            403,
            'The PDF is available after submission.'
        );

        $transferApplication->load([
            'cycle',
            'currentOffice',
            'service',
            'children',
            'workHistory',
            'preferences.office',
        ]);

        $eligibleEmployee = EligibleEmployee::query()
            ->findOrFail($request->user()->eligible_employee_id);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',

            'tempDir' => storage_path('app/mpdf-temp'),

            'default_font' => 'dejavusans',

            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
        ]);

        $html = view('pdf.transfer-application', [
            'application' => $transferApplication,
            'nic' => $eligibleEmployee->nic,
        ])->render();

        $mpdf->WriteHTML($html);

        $pdf = $mpdf->Output('', 'S');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="transfer-application-'.$transferApplication->id.'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
