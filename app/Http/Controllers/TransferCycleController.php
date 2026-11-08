<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferCycleRequest;
use App\Http\Requests\UpdateTransferCycleRequest;
use App\Models\TransferCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransferCycleController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()->can('view transfer cycles'),
            403
        );

        return Inertia::render('transfer-cycles/index', [
            'cycles' => TransferCycle::query()
                ->orderByDesc('opens_on')
                ->orderByDesc('id')
                ->get()
                ->map(fn (TransferCycle $cycle) => [
                    'id' => $cycle->id,
                    'name' => $cycle->name,
                    'opens_on' => $cycle->opens_on->format('Y-m-d'),
                    'closes_on' => $cycle->closes_on->format('Y-m-d'),
                    'status' => $cycle->status,
                    'description' => $cycle->description,
                ]),

            'canManage' => $request->user()->can(
                'manage transfer cycles'
            ),
        ]);
    }

    public function store(
        StoreTransferCycleRequest $request
    ): RedirectResponse {
        TransferCycle::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('transfer-cycles.index')
            ->with('success', 'Transfer cycle created successfully.');
    }

    public function update(
        UpdateTransferCycleRequest $request,
        TransferCycle $transferCycle
    ): RedirectResponse {
        $transferCycle->update($request->validated());

        return redirect()
            ->route('transfer-cycles.index')
            ->with('success', 'Transfer cycle updated successfully.');
    }
}
