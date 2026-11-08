<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateEmployeeRequest;
use App\Models\EligibleEmployee;
use App\Models\EmployeeActivationCode;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class EmployeeActivationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/activate-employee');
    }

    public function store(
        ActivateEmployeeRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $employee = EligibleEmployee::query()
                ->where('nic', $data['nic'])
                ->lockForUpdate()
                ->first();

            if (! $employee || ! $employee->is_active) {
                $this->invalidCode();
            }

            $alreadyLinked = User::query()
                ->where('eligible_employee_id', $employee->id)
                ->exists();

            if ($alreadyLinked) {
                $this->invalidCode();
            }

            $activation = EmployeeActivationCode::query()
                ->where('eligible_employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            $providedHash = hash(
                'sha256',
                $data['activation_code']
            );

            if (
                ! $activation
                || $activation->used_at !== null
                || $activation->expires_at->isPast()
                || ! hash_equals($activation->code_hash, $providedHash)
            ) {
                $this->invalidCode();
            }

            $role = Role::query()
                ->where('name', 'Employee')
                ->where('guard_name', 'web')
                ->first();

            if (! $role) {
                throw ValidationException::withMessages([
                    'activation_code' => 'Activation is temporarily unavailable. Please contact the administrator.',
                ]);
            }

            $user = new User();

            $user->name = $data['name'];
            $user->email = $data['email'] ?? null;
            $user->password = Hash::make($data['password']);
            $user->eligible_employee_id = $employee->id;

            $user->save();

            $user->assignRole($role);

            $activation->used_at = now();
            $activation->save();

            return $user;
        });

        if ($user->email !== null) {
            event(new Registered($user));
        }

        Auth::login($user);

        $request->session()->regenerate();

        if ($user->email !== null) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('dashboard');
    }

    private function invalidCode(): never
    {
        throw ValidationException::withMessages([
            'activation_code' => 'The NIC or activation code is invalid, expired, already used, or inactive.',
        ]);
    }
}
