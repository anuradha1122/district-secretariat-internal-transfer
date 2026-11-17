<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'name' => config('app.name'),

            'auth' => [
                'user' => $user,
            ],

            /*
             * These values control menu visibility.
             * Controllers remain responsible for authorization.
             */
            'navigation' => [
                'offices' => $user?->can('view offices') ?? false,

                'services' => $user?->can('view services') ?? false,

                'eligibleEmployees' => $user?->can(
                    'view eligible employees'
                ) ?? false,

                'activationCodes' => $user?->can(
                    'manage eligible employees'
                ) ?? false,

                'employeePasswordReset' => $user?->can(
                    'manage eligible employees'
                ) ?? false,

                'transferCycles' => $user?->can(
                    'view transfer cycles'
                ) ?? false,

                'myApplications' => $user !== null
                    && $user->eligible_employee_id !== null
                    && $user->hasRole('Employee')
                    && $user->can('view own applications'),

                'officeHeadAssignments' => $user?->can(
                    'manage users'
                ) ?? false,

                'officeReviews' => $user !== null
                    && $user->hasRole('Office Head')
                    && $user->can('view office applications'),

                'userManagement' => $user?->can(
                    'view users'
                ) ?? false,

                'districtReviews' => $user !== null
                    && $user->hasRole('District Approver')
                    && $user->can('view district applications'),

                'mutualTransfers' => $user?->can(
                    'view mutual matches'
                ) ?? false,

                'reports' => $user?->can(
                    'view district reports'
                ) ?? false,
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
        ];
    }
}