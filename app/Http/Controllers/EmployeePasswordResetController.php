<?php

namespace App\Http\Controllers;

use App\Models\EligibleEmployee;
use App\Models\EmployeePasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeePasswordResetController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/reset-employee-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nic' => [
                'required',
                'string',
                'max:12',
            ],

            'reset_code' => [
                'required',
                'string',
                'size:24',
                'regex:/^[A-Fa-f0-9]{24}$/',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(12),
            ],
        ]);

        if (config('session.driver') !== 'database') {
            throw ValidationException::withMessages([
                'reset_code' => 'Password recovery is temporarily unavailable. Contact the administrator.',
            ]);
        }

        $nic = strtoupper(trim($data['nic']));
        $providedHash = hash(
            'sha256',
            strtoupper(trim($data['reset_code']))
        );

        DB::transaction(function () use ($data, $nic, $providedHash) {
            $employee = EligibleEmployee::query()
                ->where('nic', $nic)
                ->lockForUpdate()
                ->first();

            if (! $employee || ! $employee->is_active) {
                $this->invalidCode();
            }

            $resetCode = EmployeePasswordResetCode::query()
                ->where('eligible_employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $resetCode
                || $resetCode->used_at !== null
                || $resetCode->expires_at->isPast()
                || ! hash_equals($resetCode->code_hash, $providedHash)
            ) {
                $this->invalidCode();
            }

            $user = User::query()
                ->where('eligible_employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                $this->invalidCode();
            }

            $user->password = Hash::make($data['password']);

            // Invalidate existing "remember me" cookies.
            $user->setRememberToken(Str::random(60));

            $user->save();

            $resetCode->used_at = now();
            $resetCode->save();

            // End existing sessions for this employee.
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        });

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Your password has been reset. You can now log in.'
            );
    }

    private function invalidCode(): never
    {
        throw ValidationException::withMessages([
            'reset_code' => 'The NIC or reset code is invalid, expired, already used, or inactive.',
        ]);
    }
}
