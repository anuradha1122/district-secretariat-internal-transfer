<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\OfficeReviewerAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OfficeReviewerAssignmentController extends Controller
{
    private function authorizeManagement(Request $request): void
    {
        abort_unless(
            $request->user()?->can('manage users'),
            403
        );
    }

    public function index(Request $request): Response
    {
        $this->authorizeManagement($request);

        $reviewers = User::role('Office Head')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $offices = Office::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        $assignments = OfficeReviewerAssignment::query()
            ->with([
                'user:id,name,email',
                'office:id,code,name,type,is_active',
                'assignedBy:id,name',
                'revokedBy:id,name',
            ])
            ->latest('id')
            ->get()
            ->map(fn (OfficeReviewerAssignment $assignment) => [
                'id' => $assignment->id,
                'is_active' => $assignment->is_active,
                'assigned_at' => $assignment->assigned_at?->toDateTimeString(),
                'revoked_at' => $assignment->revoked_at?->toDateTimeString(),
                'user' => $assignment->user,
                'office' => $assignment->office,
                'assigned_by' => $assignment->assignedBy,
                'revoked_by' => $assignment->revokedBy,
            ]);

        return Inertia::render('admin/office-reviewer-assignments/index', [
            'reviewers' => $reviewers,
            'offices' => $offices,
            'assignments' => $assignments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManagement($request);

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'office_id' => [
                'required',
                'integer',
                Rule::exists('offices', 'id')
                    ->where('is_active', true),
            ],
        ], [
            'user_id.required' => 'Please select an office head.',
            'user_id.exists' => 'The selected user could not be found.',
            'office_id.required' => 'Please select an office.',
            'office_id.exists' => 'Please select an active office.',
        ]);

        $reviewer = User::findOrFail($data['user_id']);

        if (! $reviewer->hasRole('Office Head')) {
            throw ValidationException::withMessages([
                'user_id' => 'The selected user must have the Office Head role.',
            ]);
        }

        DB::transaction(function () use ($request, $data): void {
            /*
             * The unique constraint prevents duplicate user/office pairs.
             * An existing inactive assignment is reactivated.
             */
            $assignment = OfficeReviewerAssignment::query()
                ->firstOrCreate(
                    [
                        'user_id' => $data['user_id'],
                        'office_id' => $data['office_id'],
                    ],
                    [
                        'is_active' => false,
                    ]
                );

            $assignment = OfficeReviewerAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($assignment->is_active) {
                throw ValidationException::withMessages([
                    'office_id' => 'This office head is already assigned to the selected office.',
                ]);
            }

            $assignment->update([
                'is_active' => true,
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
                'revoked_by' => null,
                'revoked_at' => null,
            ]);
        });

        return redirect()
            ->route('office-reviewer-assignments.index')
            ->with('success', 'Office head assigned successfully.');
    }

    public function destroy(
        Request $request,
        OfficeReviewerAssignment $officeReviewerAssignment
    ): RedirectResponse {
        $this->authorizeManagement($request);

        DB::transaction(function () use ($request, $officeReviewerAssignment): void {
            $assignment = OfficeReviewerAssignment::query()
                ->whereKey($officeReviewerAssignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $assignment->is_active) {
                return;
            }

            $assignment->update([
                'is_active' => false,
                'revoked_by' => $request->user()->id,
                'revoked_at' => now(),
            ]);
        });

        return redirect()
            ->route('office-reviewer-assignments.index')
            ->with('success', 'Office head assignment revoked.');
    }
}
