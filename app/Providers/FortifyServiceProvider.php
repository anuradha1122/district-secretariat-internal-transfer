<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use App\Models\EligibleEmployee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthentication();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $identifier = trim((string) $request->input('email'));
            $password = $request->input('password');

            if ($identifier === '' || ! is_string($password)) {
                return null;
            }

            if (str_contains($identifier, '@')) {
                $user = User::query()
                    ->where('email', strtolower($identifier))
                    ->first();
            } else {
                $nic = strtoupper($identifier);

                $employee = EligibleEmployee::query()
                    ->where('nic', $nic)
                    ->where('is_active', true)
                    ->first();

                $user = $employee
                    ? User::query()
                        ->where('eligible_employee_id', $employee->id)
                        ->first()
                    : null;
            }

            if (! $user || ! Hash::check($password, $user->password)) {
                return null;
            }

            // Also block inactive employees who attempt to log in
            // using an email address.
            if ($user->eligible_employee_id !== null) {
                $active = EligibleEmployee::query()
                    ->whereKey($user->eligible_employee_id)
                    ->where('is_active', true)
                    ->exists();

                if (! $active) {
                    return null;
                }
            }

            return $user;
        });
    }
}
