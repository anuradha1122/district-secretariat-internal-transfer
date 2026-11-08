<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EligibleEmployee;
use App\Models\EmployeePasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeePasswordResetCodeController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()->can('manage eligible employees'),
            403
        );

        return Inertia::render('employee-password-reset-codes/index', [
            'issuedCode' => $request->session()->pull(
                'issued_employee_password_reset_code'
            ),
        ]);
    }

    public function issue(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user()->can('manage eligible employees'),
            403
        );

        $data = $request->validate([
            'nic' => ['required', 'string', 'max:12'],
        ]);

        $nic = strtoupper(trim($data['nic']));
        $plainCode = strtoupper(bin2hex(random_bytes(12)));

        DB::transaction(function () use (
            $request,
            $nic,
            $plainCode
        ) {
            $employee = EligibleEmployee::query()
                ->where('nic', $nic)
                ->lockForUpdate()
                ->first();

            if (! $employee || ! $employee->is_active) {
                throw ValidationException::withMessages([
                    'nic' => 'No active employee account was found for this NIC.',
                ]);
            }

            $userExists = User::query()
                ->where('eligible_employee_id', $employee->id)
                ->exists();

            if (! $userExists) {
                throw ValidationException::withMessages([
                    'nic' => 'No active employee account was found for this NIC.',
                ]);
            }

            EmployeePasswordResetCode::updateOrCreate(
                [
                    'eligible_employee_id' => $employee->id,
                ],
                [
                    'code_hash' => hash('sha256', $plainCode),
                    'expires_at' => now()->addHours(24),
                    'used_at' => null,
                    'issued_by' => $request->user()->id,
                ]
            );
        });

        $request->session()->flash(
            'issued_employee_password_reset_code',
            [
                'nic' => $nic,
                'code' => $plainCode,
            ]
        );

        return redirect()->route('employee-reset-codes.index');
    }

    public function revoke(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user()->can('manage eligible employees'),
            403
        );

        $data = $request->validate([
            'nic' => ['required', 'string', 'max:12'],
        ]);

        $nic = strtoupper(trim($data['nic']));

        DB::transaction(function () use ($nic) {
            $employee = EligibleEmployee::query()
                ->where('nic', $nic)
                ->lockForUpdate()
                ->first();

            if ($employee) {
                EmployeePasswordResetCode::query()
                    ->where('eligible_employee_id', $employee->id)
                    ->whereNull('used_at')
                    ->delete();
            }
        });

        return redirect()
            ->route('employee-reset-codes.index')
            ->with('success', 'Any unused reset code has been revoked.');
    }
}
