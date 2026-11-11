<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\TransferCycle;
use App\Services\Transfers\MutualTransferPreviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MutualTransferPreviewController extends Controller
{
    public function index(
        Request $request,
        MutualTransferPreviewService $previewService
    ): Response {
        abort_unless(
            $request->user()?->can('view mutual matches'),
            403
        );

        $data = $request->validate([
            'cycle_id' => [
                'nullable',
                'integer',
                Rule::exists('transfer_cycles', 'id'),
                'required_with:service_id',
            ],

            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id'),
                'required_with:cycle_id',
            ],
        ], [
            'cycle_id.required_with' => 'Please select a transfer cycle.',
            'service_id.required_with' => 'Please select a service.',
        ]);

        $cycleId = isset($data['cycle_id'])
            ? (int) $data['cycle_id']
            : null;

        $serviceId = isset($data['service_id'])
            ? (int) $data['service_id']
            : null;

        $candidates = $cycleId && $serviceId
            ? $previewService->preview($cycleId, $serviceId)
            : [];

        return Inertia::render('mutual-transfers/index', [
            'cycles' => TransferCycle::query()
                ->orderByDesc('id')
                ->get(['id', 'name']),

            'services' => Service::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            'filters' => [
                'cycle_id' => $cycleId,
                'service_id' => $serviceId,
            ],

            'candidates' => $candidates,
        ]);
    }
}
