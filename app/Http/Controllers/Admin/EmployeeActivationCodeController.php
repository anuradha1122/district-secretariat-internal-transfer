<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EligibleEmployee;
use App\Models\EmployeeActivationCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeActivationCodeController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()->can('manage eligible employees'),
            403
        );

        $search = trim((string) $request->query('search', ''));

        $employees = EligibleEmployee::query()
            ->select(['id', 'nic', 'is_active'])
            ->with([
                'activationCode:id,eligible_employee_id,expires_at,used_at',
            ])
            ->withExists('user')
            ->when(
                $search !== '',
                fn ($query) => $query->where('nic', 'like', "%{$search}%")
            )
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('employee-activation-codes/index', [
            'employees' => $employees,
            'filters' => [
                'search' => $search,
            ],

            // Read once after issuing. The raw code is not stored
            // in the activation-code database table.
            'issuedCode' => $request->session()->pull('issued_activation_code'),
        ]);
    }

    public function issue(
        Request $request,
        EligibleEmployee $eligibleEmployee
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('manage eligible employees'),
            403
        );

        $plainCode = strtoupper(bin2hex(random_bytes(12)));

        DB::transaction(function () use (
            $eligibleEmployee,
            $request,
            $plainCode
        ) {
            $employee = EligibleEmployee::query()
                ->whereKey($eligibleEmployee->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $employee->is_active) {
                throw ValidationException::withMessages([
                    'activation' => 'An activation code cannot be issued for an inactive NIC.',
                ]);
            }

            $hasAccount = User::query()
                ->where('eligible_employee_id', $employee->id)
                ->exists();

            if ($hasAccount) {
                throw ValidationException::withMessages([
                    'activation' => 'This employee already has an account.',
                ]);
            }

            EmployeeActivationCode::updateOrCreate(
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

        $request->session()->flash('issued_activation_code', [
            'nic' => $eligibleEmployee->nic,
            'code' => $plainCode,
        ]);

        return redirect()->route('activation-codes.index');
    }

    public function revoke(
        Request $request,
        EligibleEmployee $eligibleEmployee
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('manage eligible employees'),
            403
        );

        DB::transaction(function () use ($eligibleEmployee) {
            $employee = EligibleEmployee::query()
                ->whereKey($eligibleEmployee->id)
                ->lockForUpdate()
                ->firstOrFail();

            EmployeeActivationCode::query()
                ->where('eligible_employee_id', $employee->id)
                ->whereNull('used_at')
                ->delete();
        });

        return redirect()->route('activation-codes.index');
    }
}
