<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficeReviewerAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * These are the staff roles managed from this page.
     *
     * Employee is managed through employee activation.
     * Super Admin is not managed through this page.
     */
    private const STAFF_ROLES = [
        'Admin',
        'Data Entry Officer',
        'Office Head',
        'District Approver',
        'Auditor',
    ];

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()?->can('view users'),
            403
        );
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole('Super Admin')
            && $request->user()?->can('manage users'),
            403
        );
    }

    public function index(Request $request): Response
    {
        $this->authorizeView($request);

        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
                'is_employee_account' => $user->eligible_employee_id !== null,
                'roles' => $user->roles
                    ->pluck('name')
                    ->sort()
                    ->values(),
            ]);

        $availableRoles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::STAFF_ROLES)
            ->orderBy('name')
            ->pluck('name');

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'availableRoles' => $availableRoles,
            'filters' => [
                'search' => $search,
            ],
            'canManage' => $request->user()->hasRole('Super Admin')
                && $request->user()->can('manage users'),
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManagement($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(12)->letters()->numbers(),
            ],

            'roles' => ['required', 'array', 'min:1'],

            'roles.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(self::STAFF_ROLES),
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'web'),
            ],

            'email_verified' => ['accepted'],
        ], [
            'roles.required' => 'Please select at least one staff role.',
            'roles.min' => 'Please select at least one staff role.',
            'email_verified.accepted' => 'Please confirm that the email address has been independently verified.',
        ]);

        $email = strtolower(trim($data['email']));

        /*
         * Recheck the normalized address. This matters where the database
         * uses a case-sensitive collation.
         */
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email address is already in use.',
            ]);
        }

        DB::transaction(function () use ($data, $email): void {
            $user = new User();

            $user->name = trim($data['name']);
            $user->email = $email;
            $user->password = Hash::make($data['password']);

            /*
             * The administrator explicitly confirmed independent
             * verification. No verification email is sent here.
             */
            $user->email_verified_at = now();

            $user->save();

            $user->assignRole($data['roles']);
        });

        return redirect()
            ->route('user-management.index')
            ->with('success', 'User created successfully.');
    }

    public function updateRoles(
        Request $request,
        User $managedUser
    ): RedirectResponse {
        $this->authorizeManagement($request);

        if ($request->user()->is($managedUser)) {
            abort(403, 'You cannot change your own roles here.');
        }

        if ($managedUser->hasRole('Super Admin')) {
            abort(403, 'Super Admin roles cannot be changed here.');
        }

        $data = $request->validate([
            'roles' => ['present', 'array'],

            'roles.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(self::STAFF_ROLES),
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'web'),
            ],
        ]);

        DB::transaction(function () use ($request, $managedUser, $data): void {
            $user = User::query()
                ->whereKey($managedUser->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Recheck after acquiring the lock.
             */
            if ($user->hasRole('Super Admin')) {
                abort(403, 'Super Admin roles cannot be changed here.');
            }

            $previousRoles = $user->getRoleNames()->all();

            $rolesToRemove = array_values(array_intersect(
                self::STAFF_ROLES,
                $previousRoles
            ));

            $rolesToRemove = array_values(array_diff(
                $rolesToRemove,
                $data['roles']
            ));

            $rolesToAdd = array_values(array_diff(
                $data['roles'],
                $previousRoles
            ));

            foreach ($rolesToRemove as $roleName) {
                $user->removeRole($roleName);
            }

            foreach ($rolesToAdd as $roleName) {
                $user->assignRole($roleName);
            }

            /*
             * If the Office Head role is removed, revoke active office
             * assignments. Re-adding the role later does not silently
             * restore those assignments.
             */
            if (in_array('Office Head', $rolesToRemove, true)) {
                OfficeReviewerAssignment::query()
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'revoked_by' => $request->user()->id,
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        });

        return redirect()
            ->route('user-management.index')
            ->with('success', 'User roles updated successfully.');
    }
}
