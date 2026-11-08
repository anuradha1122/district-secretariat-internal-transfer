<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeProfileRequest;
use App\Models\EmployeeProfile;
use App\Models\Office;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            $user->eligible_employee_id !== null
                && $user->hasRole('Employee'),
            403
        );

        $profile = EmployeeProfile::query()
            ->where('user_id', $user->id)
            ->first();

        return Inertia::render('employee-profile/edit', [
            'profile' => $profile,

            'offices' => Office::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function update(
        UpdateEmployeeProfileRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        EmployeeProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return redirect()
            ->route('employee-profile.edit')
            ->with('success', 'Your profile has been saved successfully.');
    }
}
