<?php

namespace App\Http\Controllers;

use App\Models\EligibleEmployee;
use App\Models\OfficeReviewerAssignment;
use App\Models\TransferApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $cards = [];
        $latestApplication = null;

        /*
         * Notifications are always limited to the current account.
         */
        $unreadNotifications = DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('type', 'employee_application_update')
            ->whereNull('read_at')
            ->count();

        $cards[] = [
            'key' => 'notifications',
            'title' => 'Unread notifications',
            'value' => $unreadNotifications,
            'description' => 'Updates for your account',
            'href' => '/notifications',
            'icon' => 'bell',
        ];

        /*
         * Employee information is limited to the signed-in user's own
         * applications.
         */
        if (
            $user->eligible_employee_id !== null
            && $user->hasRole('Employee')
            && $user->can('view own applications')
        ) {
            $ownApplications = TransferApplication::query()
                ->where('user_id', $user->id);

            $cards[] = [
                'key' => 'my-applications',
                'title' => 'My applications',
                'value' => (clone $ownApplications)->count(),
                'description' => 'Applications across all cycles',
                'href' => '/transfer-applications',
                'icon' => 'file',
            ];

            $cards[] = [
                'key' => 'my-drafts',
                'title' => 'Draft applications',
                'value' => (clone $ownApplications)
                    ->where('status', 'draft')
                    ->count(),
                'description' => 'Applications to complete or resubmit',
                'href' => '/transfer-applications',
                'icon' => 'edit',
            ];

            $latest = (clone $ownApplications)
                ->with('cycle:id,name')
                ->latest('id')
                ->first();

            if ($latest) {
                $latestApplication = [
                    'reference_no' => $latest->reference_no,
                    'cycle' => $latest->cycle?->name,
                    'status' => $latest->status,
                    'submitted_at' => $latest->submitted_at?->format('Y-m-d'),
                ];
            }
        }

        /*
         * Office review counts use active assignments for active offices.
         * The reviewer's own application is excluded.
         */
        if (
            $user->hasRole('Office Head')
            && $user->can('view office applications')
        ) {
            $assignedOfficeIds = OfficeReviewerAssignment::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->whereHas('office', function ($query): void {
                    $query->where('is_active', true);
                })
                ->select('office_id');

            $officePending = TransferApplication::query()
                ->whereIn('current_office_id', $assignedOfficeIds)
                ->where('status', 'submitted')
                ->where('user_id', '!=', $user->id)
                ->count();

            $cards[] = [
                'key' => 'office-pending',
                'title' => 'Office review pending',
                'value' => $officePending,
                'description' => 'Submitted applications in your assigned offices',
                'href' => '/office-reviews',
                'icon' => 'office',
            ];
        }

        /*
         * This is the district-wide total, not a count of decisions that
         * the current user is personally permitted to make.
         */
        if (
            $user->hasRole('District Approver')
            && $user->can('view district applications')
        ) {
            $districtPending = TransferApplication::query()
                ->where('status', 'office_recommended')
                ->count();

            $cards[] = [
                'key' => 'district-pending',
                'title' => 'District review pending',
                'value' => $districtPending,
                'description' => 'District-wide applications forwarded by offices',
                'href' => '/district-reviews',
                'icon' => 'review',
            ];
        }

        if ($user->can('view eligible employees')) {
            $cards[] = [
                'key' => 'eligible-employees',
                'title' => 'Eligible employees',
                'value' => EligibleEmployee::query()->count(),
                'description' => 'All employee records',
                'href' => '/eligible-employees',
                'icon' => 'users',
            ];
        }

        if ($user->can('view offices')) {
            $cards[] = [
                'key' => 'offices',
                'title' => 'Offices',
                'value' => DB::table('offices')->count(),
                'description' => 'All registered offices',
                'href' => '/offices',
                'icon' => 'office',
            ];
        }

        if ($user->can('view services')) {
            $cards[] = [
                'key' => 'services',
                'title' => 'Services',
                'value' => DB::table('services')->count(),
                'description' => 'All registered services',
                'href' => '/services',
                'icon' => 'service',
            ];
        }

        $quickLinks = [];

        if ($user->can('view mutual matches')) {
            $quickLinks[] = [
                'title' => 'Mutual transfer preview',
                'description' => 'View potential reciprocal transfer matches.',
                'href' => '/mutual-transfers',
            ];
        }

        if ($user->can('view district reports')) {
            $quickLinks[] = [
                'title' => 'Management reports',
                'description' => 'View and export available district reports.',
                'href' => '/reports',
            ];
        }

        if ($user->can('view users')) {
            $quickLinks[] = [
                'title' => 'User management',
                'description' => 'View user accounts and their roles.',
                'href' => '/user-management',
            ];
        }

        return Inertia::render('dashboard', [
            'dashboardData' => [
                'name' => $user->name,
                'roles' => $user->getRoleNames()->values(),
                'cards' => $cards,
                'latestApplication' => $latestApplication,
                'quickLinks' => $quickLinks,
            ],
        ]);
    }
}