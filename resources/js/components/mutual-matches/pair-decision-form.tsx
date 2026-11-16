import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    runId: number;
    applicationAId: number;
    applicationBId: number;
};

export default function PairDecisionForm({
    runId,
    applicationAId,
    applicationBId,
}: Props) {
    const form = useForm({
        application_a_id: applicationAId,
        application_b_id: applicationBId,
        outcome: '',
        remarks: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const confirmed = window.confirm(
            form.data.outcome === 'confirmed'
                ? 'Confirm this pair as a mutual transfer recommendation?'
                : 'Reject this suggested pair?',
        );

        if (!confirmed) {
            return;
        }

        form.post(`/mutual-match-runs/${runId}/decisions`, {
            preserveScroll: true,
        });
    }

    return (
        <form
            onSubmit={submit}
            className="mt-5 space-y-3 border-t border-gray-200 pt-4 dark:border-gray-700"
        >
            <div>
                <label
                    htmlFor={`outcome-${applicationAId}-${applicationBId}`}
                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    Pair decision <span className="text-red-600">*</span>
                </label>

                <select
                    id={`outcome-${applicationAId}-${applicationBId}`}
                    value={form.data.outcome}
                    onChange={(event) =>
                        form.setData('outcome', event.target.value)
                    }
                    required
                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                >
                    <option value="">Select a decision</option>
                    <option value="confirmed">Confirm pair</option>
                    <option value="rejected">Reject pair</option>
                </select>

                {form.errors.outcome && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                        {form.errors.outcome}
                    </p>
                )}
            </div>

            <div>
                <label
                    htmlFor={`remarks-${applicationAId}-${applicationBId}`}
                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    Remarks
                    {form.data.outcome === 'rejected' && (
                        <span className="text-red-600"> *</span>
                    )}
                </label>

                <textarea
                    id={`remarks-${applicationAId}-${applicationBId}`}
                    rows={3}
                    maxLength={5000}
                    value={form.data.remarks}
                    onChange={(event) =>
                        form.setData('remarks', event.target.value)
                    }
                    required={form.data.outcome === 'rejected'}
                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                />

                {form.errors.remarks && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                        {form.errors.remarks}
                    </p>
                )}
            </div>

            {form.errors.pair && (
                <p className="text-sm text-red-600 dark:text-red-400">
                    {form.errors.pair}
                </p>
            )}

            <button
                type="submit"
                disabled={form.processing}
                className="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
            >
                {form.processing ? 'Saving...' : 'Record Decision'}
            </button>
        </form>
    );
}
