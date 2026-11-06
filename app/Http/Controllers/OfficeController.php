<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    public function index(): Response
    {
        abort_unless(
            auth()->user()->can('view offices'),
            403
        );

        return Inertia::render('offices/index', [
            'offices' => Office::query()
                ->with('parent:id,name')
                ->orderBy('name')
                ->get(),

            'districtOffices' => Office::query()
                ->where('type', 'district_secretariat')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'canManage' => auth()->user()->can('manage offices'),
        ]);
    }

    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        Office::create($request->validated());

        return redirect()
            ->route('offices.index')
            ->with('success', 'Office created successfully.');
    }

    public function update(
        UpdateOfficeRequest $request,
        Office $office
    ): RedirectResponse {
        $data = $request->validated();

        if (
            $office->type === 'district_secretariat'
            && $office->children()->exists()
            && (
                $data['type'] !== 'district_secretariat'
                || ! $data['is_active']
            )
        ) {
            return back()->withErrors([
                'type' => 'A district office with divisional offices cannot be deactivated or changed to another type.',
            ]);
        }

        $office->update($data);

        return redirect()
            ->route('offices.index')
            ->with('success', 'Office updated successfully.');
    }
}
