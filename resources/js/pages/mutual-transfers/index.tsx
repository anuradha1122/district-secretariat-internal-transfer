import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

type Cycle = {
    id: number;
    name: string;
};

type Service = {
    id: number;
    code: string;
    name: string;
};

type Office = {
    id: number;
    code: string;
    name: string;
};

type MatchApplication = {
    id: number;
    reference_no: string | null;
    name: string | null;
    post: string | null;
    grade: string | null;
    office: Office | null;
};

type Candidate = {
    application_a: MatchApplication;
    application_b: MatchApplication;
    rank_a: number;
    rank_b: number;
    score: number;
    worst_rank: number;
    suggestion: 'proposed' | 'conflict';
};

type Props = {
    cycles: Cycle[];
    services: Service[];
    filters: {
        cycle_id: number | null;
        service_id: number | null;
    };
    candidates: Candidate[];
};

function Applicant({
    applicant,
    rank,
}: {
    applicant: MatchApplication;
    rank: number;
}) {
    return (
        <div>
            <p className="font-medium text-gray-900 dark:text-gray-100">
                {applicant.name ?? '—'}
            </p>

            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {applicant.reference_no ?? '—'}
            </p>

            <p className="mt-2 text-sm text-gray-700 dark:text-gray-200">
                Current office: {applicant.office?.name ?? '—'}
            </p>

            <p className="mt-1 text-sm text-gray-700 dark:text-gray-200">
                Preference for the other office: {rank}
            </p>

            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {[applicant.post, applicant.grade].filter(Boolean).join(' · ') || '—'}
            </p>
        </div>
    );
}

export default function MutualTransferIndex({
    cycles,
    services,
    filters,
    candidates,
}: Props) {
    const [cycleId, setCycleId] = useState(
        filters.cycle_id ? String(filters.cycle_id) : '',
    );

    const [serviceId, setServiceId] = useState(
        filters.service_id ? String(filters.service_id) : '',
    );

    const hasFilters = filters.cycle_id !== null && filters.service_id !== null;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        router.get(
            '/mutual-transfers',
            {
                cycle_id: cycleId,
                service_id: serviceId,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    }

    const selectClass =
        'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100';

    return (
        <>
            <Head title="Mutual Transfer Preview" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Mutual Transfer Preview
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Review reciprocal preferences for approved applications.
                    </p>
                </div>

                <div className="rounded-md bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                    This is a live preview. Suggestions are not saved or confirmed,
                    and no transfer order is created.
                </div>

                <form
                    onSubmit={submit}
                    className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <label
                                htmlFor="cycle_id"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Transfer cycle <span className="text-red-600">*</span>
                            </label>

                            <select
                                id="cycle_id"
                                value={cycleId}
                                onChange={(event) => setCycleId(event.target.value)}
                                className={selectClass}
                                required
                            >
                                <option value="">Select a cycle</option>

                                {cycles.map((cycle) => (
                                    <option key={cycle.id} value={cycle.id}>
                                        {cycle.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label
                                htmlFor="service_id"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Service <span className="text-red-600">*</span>
                            </label>

                            <select
                                id="service_id"
                                value={serviceId}
                                onChange={(event) => setServiceId(event.target.value)}
                                className={selectClass}
                                required
                            >
                                <option value="">Select a service</option>

                                {services.map((service) => (
                                    <option key={service.id} value={service.id}>
                                        {service.code} — {service.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <button
                        type="submit"
                        className="mt-4 rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                    >
                        Preview Matches
                    </button>
                </form>

                {hasFilters && (
                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Candidate pairs
                            </h2>

                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {candidates.length} candidate pair(s) found.
                            </p>
                        </div>

                        {candidates.length === 0 ? (
                            <div className="rounded-lg border border-gray-200 bg-white p-5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                No reciprocal matches were found for the selected cycle
                                and service.
                            </div>
                        ) : (
                            candidates.map((candidate) => (
                                <div
                                    key={`${candidate.application_a.id}-${candidate.application_b.id}`}
                                    className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <span
                                            className={
                                                candidate.suggestion === 'proposed'
                                                    ? 'rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-200'
                                                    : 'rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200'
                                            }
                                        >
                                            {candidate.suggestion === 'proposed'
                                                ? 'Proposed pair'
                                                : 'Conflict alternative'}
                                        </span>

                                        <span className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Score: {candidate.score}
                                        </span>
                                    </div>

                                    <div className="mt-4 grid gap-5 md:grid-cols-2">
                                        <Applicant
                                            applicant={candidate.application_a}
                                            rank={candidate.rank_a}
                                        />

                                        <Applicant
                                            applicant={candidate.application_b}
                                            rank={candidate.rank_b}
                                        />
                                    </div>
                                </div>
                            ))
                        )}
                    </section>
                )}
            </div>
        </>
    );
}
