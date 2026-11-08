<?php

namespace App\Http\Middleware;

use App\Models\EligibleEmployee;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeIsActiveAndEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->eligible_employee_id !== null) {
            $isActive = EligibleEmployee::query()
                ->whereKey($user->eligible_employee_id)
                ->where('is_active', true)
                ->exists();

            abort_unless($isActive, 403, 'Your employee account is inactive.');

            // NIC-activated employees without email do not need
            // email verification.
            if ($user->email === null) {
                return $next($request);
            }
        }

        // For administrators and employees who supplied an email,
        // retain Laravel's normal verification check.
        return app(EnsureEmailIsVerified::class)
            ->handle($request, $next);
    }
}
