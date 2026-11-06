<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        abort_unless(
            auth()->user()->can('view services'),
            403
        );

        return Inertia::render('services/index', [
            'services' => Service::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'is_active']),

            'canManage' => auth()->user()->can('manage services'),
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return redirect()
            ->route('services.index')
            ->with('success', 'Service created successfully.');
    }

    public function update(
        UpdateServiceRequest $request,
        Service $service
    ): RedirectResponse {
        $service->update($request->validated());

        return redirect()
            ->route('services.index')
            ->with('success', 'Service updated successfully.');
    }
}
