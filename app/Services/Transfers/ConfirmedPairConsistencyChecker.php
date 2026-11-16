<?php

namespace App\Services\Transfers;

class ConfirmedPairConsistencyChecker
{
    /**
     * Compare a saved candidate with current application data.
     *
     * Expected current application structure:
     *
     * [
     *     'id' => 1,
     *     'current_office_id' => 10,
     *     'preferences' => [
     *         ['office_id' => 20, 'priority' => 1],
     *     ],
     * ]
     *
     * @param array<string, mixed> $candidate
     * @param array<string, mixed>|null $applicationA
     * @param array<string, mixed>|null $applicationB
     * @return array<int, string>
     */
    public function check(
        array $candidate,
        ?array $applicationA,
        ?array $applicationB
    ): array {
        if (! $applicationA || ! $applicationB) {
            return ['One or both applications are unavailable.'];
        }

        $savedA = $candidate['application_a'] ?? null;
        $savedB = $candidate['application_b'] ?? null;

        if (
            ! is_array($savedA)
            || ! is_array($savedB)
            || ! isset($savedA['id'], $savedB['id'])
            || ! isset($savedA['office']['id'], $savedB['office']['id'])
            || ! isset($candidate['rank_a'], $candidate['rank_b'])
        ) {
            return ['The saved candidate snapshot is incomplete.'];
        }

        /*
         * Confirm that the caller supplied the applications belonging
         * to the saved candidate in the correct order.
         */
        if (
            (int) ($applicationA['id'] ?? 0) !== (int) $savedA['id']
            || (int) ($applicationB['id'] ?? 0) !== (int) $savedB['id']
        ) {
            return ['The current applications do not match the saved pair.'];
        }

        $currentOfficeA = $applicationA['current_office_id'] ?? null;
        $currentOfficeB = $applicationB['current_office_id'] ?? null;

        $issues = [];

        if (
            $currentOfficeA === null
            || (int) $currentOfficeA !== (int) $savedA['office']['id']
        ) {
            $issues[] = 'The first employee’s current office has changed.';
        }

        if (
            $currentOfficeB === null
            || (int) $currentOfficeB !== (int) $savedB['office']['id']
        ) {
            $issues[] = 'The second employee’s current office has changed.';
        }

        /*
         * If a current office is missing, the reciprocal preference
         * cannot be checked reliably.
         */
        if ($currentOfficeA === null || $currentOfficeB === null) {
            return $issues;
        }

        $preferenceA = $this->preferenceForOffice(
            $applicationA['preferences'] ?? [],
            (int) $currentOfficeB
        );

        $preferenceB = $this->preferenceForOffice(
            $applicationB['preferences'] ?? [],
            (int) $currentOfficeA
        );

        if ($preferenceA === null) {
            $issues[] = 'The first employee no longer prefers the second employee’s current office.';
        } elseif (
            (int) $preferenceA['priority']
            !== (int) $candidate['rank_a']
        ) {
            $issues[] = 'The first employee’s reciprocal preference priority has changed.';
        }

        if ($preferenceB === null) {
            $issues[] = 'The second employee no longer prefers the first employee’s current office.';
        } elseif (
            (int) $preferenceB['priority']
            !== (int) $candidate['rank_b']
        ) {
            $issues[] = 'The second employee’s reciprocal preference priority has changed.';
        }

        return $issues;
    }

    /**
     * @param mixed $preferences
     * @return array<string, mixed>|null
     */
    private function preferenceForOffice(
        mixed $preferences,
        int $officeId
    ): ?array {
        if (! is_array($preferences)) {
            return null;
        }

        foreach ($preferences as $preference) {
            if (
                is_array($preference)
                && isset($preference['office_id'], $preference['priority'])
                && (int) $preference['office_id'] === $officeId
            ) {
                return $preference;
            }
        }

        return null;
    }
}