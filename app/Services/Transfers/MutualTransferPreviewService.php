<?php

namespace App\Services\Transfers;

use App\Models\TransferApplication;
use Illuminate\Support\Collection;

class MutualTransferPreviewService
{
    /**
     * Build a temporary list of reciprocal two-person matches.
     *
     * Only approved applications belonging to active eligible employees
     * in the selected cycle and service are included.
     *
     * @return array<int, array<string, mixed>>
     */
    /**
 * Build a temporary list of reciprocal matches.
 *
 * @return array<int, array<string, mixed>>
 */
public function preview(int $cycleId, int $serviceId): array
{
    return $this->buildCandidates(
        $this->eligibleApplications($cycleId, $serviceId)
    );
}

/**
 * Fetch the approved applications used as matching inputs.
 *
 * @return \Illuminate\Support\Collection<int, TransferApplication>
 */
public function eligibleApplications(int $cycleId, int $serviceId): Collection
{
    return TransferApplication::query()
        ->select('transfer_applications.*')
        ->join(
            'users',
            'users.id',
            '=',
            'transfer_applications.user_id'
        )
        ->join(
            'eligible_employees',
            'eligible_employees.id',
            '=',
            'users.eligible_employee_id'
        )
        ->where('eligible_employees.is_active', true)
        ->where('transfer_applications.transfer_cycle_id', $cycleId)
        ->where('transfer_applications.service_id', $serviceId)
        ->where('transfer_applications.status', 'approved')
        ->whereNotNull('transfer_applications.current_office_id')
        ->with([
            'currentOffice:id,code,name',
            'service:id,code,name',
            'preferences:id,transfer_application_id,office_id,priority',
        ])
        ->orderBy('transfer_applications.id')
        ->get();
}

    /**
     * The collection must contain approved applications from one cycle
     * and one service with their currentOffice and preferences loaded.
     *
     * @param Collection<int, TransferApplication> $applications
     * @return array<int, array<string, mixed>>
     */
    public function buildCandidates(Collection $applications): array
    {
        $byOffice = $applications->groupBy('current_office_id');

        $candidates = [];

        foreach ($applications as $applicationA) {
            foreach ($applicationA->preferences as $preferenceA) {
                $destinationOfficeId = (int) $preferenceA->office_id;

                if (
                    $destinationOfficeId === (int) $applicationA->current_office_id
                ) {
                    continue;
                }

                $possiblePartners = $byOffice->get(
                    $destinationOfficeId,
                    collect()
                );

                foreach ($possiblePartners as $applicationB) {
                    /*
                     * Use IDs to include each pair only once.
                     */
                    if ($applicationA->id >= $applicationB->id) {
                        continue;
                    }

                    /*
                     * Defend against incomplete or inconsistent data even
                     * if this method is used elsewhere in the future.
                     */
                    if (
                        $applicationA->user_id === $applicationB->user_id
                        || $applicationA->transfer_cycle_id
                            !== $applicationB->transfer_cycle_id
                        || $applicationA->service_id
                            !== $applicationB->service_id
                        || $applicationA->status !== 'approved'
                        || $applicationB->status !== 'approved'
                    ) {
                        continue;
                    }

                    $preferenceB = $applicationB->preferences
                        ->first(
                            fn ($preference) =>
                                (int) $preference->office_id
                                === (int) $applicationA->current_office_id
                        );

                    if (! $preferenceB) {
                        continue;
                    }

                    $rankA = (int) $preferenceA->priority;
                    $rankB = (int) $preferenceB->priority;

                    $submittedA = $applicationA->submitted_at?->getTimestamp();
                    $submittedB = $applicationB->submitted_at?->getTimestamp();

                    /*
                     * Missing timestamps sort after valid timestamps.
                     */
                    $laterSubmittedAt = (
                        $submittedA !== null && $submittedB !== null
                    )
                        ? max($submittedA, $submittedB)
                        : PHP_INT_MAX;

                    $candidates[] = [
                        'application_a' => $this->applicationData($applicationA),
                        'application_b' => $this->applicationData($applicationB),
                        'rank_a' => $rankA,
                        'rank_b' => $rankB,
                        'score' => $rankA + $rankB,
                        'worst_rank' => max($rankA, $rankB),
                        'later_submitted_at_sort' => $laterSubmittedAt,
                    ];
                }
            }
        }

        usort($candidates, function (array $a, array $b): int {
            return [
                $a['score'],
                $a['worst_rank'],
                $a['later_submitted_at_sort'],
                $a['application_a']['id'],
                $a['application_b']['id'],
            ] <=> [
                $b['score'],
                $b['worst_rank'],
                $b['later_submitted_at_sort'],
                $b['application_a']['id'],
                $b['application_b']['id'],
            ];
        });

        /*
         * Greedy proposal: once an applicant is included in a proposed
         * pair, other candidate pairs remain visible as alternatives.
         */
        $usedApplicationIds = [];

        foreach ($candidates as &$candidate) {
            $idA = $candidate['application_a']['id'];
            $idB = $candidate['application_b']['id'];

            $available = ! isset($usedApplicationIds[$idA])
                && ! isset($usedApplicationIds[$idB]);

            $candidate['suggestion'] = $available
                ? 'proposed'
                : 'conflict';

            if ($available) {
                $usedApplicationIds[$idA] = true;
                $usedApplicationIds[$idB] = true;
            }

            unset($candidate['later_submitted_at_sort']);
        }

        unset($candidate);

        return $candidates;
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationData(TransferApplication $application): array
    {
        return [
            'id' => $application->id,
            'reference_no' => $application->reference_no,
            'name' => $application->full_name,
            'post' => $application->post,
            'grade' => $application->grade,
            'office' => $application->currentOffice
                ? [
                    'id' => $application->currentOffice->id,
                    'code' => $application->currentOffice->code,
                    'name' => $application->currentOffice->name,
                ]
                : null,
        ];
    }
}
