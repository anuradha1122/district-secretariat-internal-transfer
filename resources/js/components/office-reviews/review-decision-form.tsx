import { useForm } from '@inertiajs/react';

type Props = {
    applicationId: number;
};

type Outcome = 'forward' | 'returned';

type Recommendation = '' | 'recommended' | 'not_recommended';

export default function ReviewDecisionForm({ applicationId }: Props) {
    const form = useForm<{
        outcome: Outcome;
        recommendation: Recommendation;
        insufficient_service: boolean;
        replacement_required: boolean;
        pending_matters: boolean;
        other_circumstances: boolean;
        signed_paper_verified: boolean;
        remarks: string;
    }>({
        outcome: 'forward',
        recommendation: '',
        insufficient_service: false,
        replacement_required: false,
        pending_matters: false,
        other_circumstances: false,
        signed_paper_verified: false,
        remarks: '',
    });

    function submit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const message =
            form.data.outcome === 'forward'
                ? 'Forward this recommendation to the District Secretariat?'
                : 'Return this application to the employee for correction?';

        if (!window.confirm(message)) {
            return;
        }

        form.post(`/office-reviews/${applicationId}/decision`, {
            preserveScroll: true,
        });
    }

    const checkboxClass =
        'mt-1 rounded border-gray-300 text-blue-700 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800';

    const labelClass =
        'flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200';

    return (
        <section className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Office Head Review
            </h2>

            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Record your recommendation or return the application for correction.
            </p>

            <form onSubmit={submit} className="mt-5 space-y-5">
                <fieldset>
                    <legend className="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Action <span className="text-red-600">*</span>
                    </legend>

                    <div className="mt-2 space-y-2">
                        <label className={labelClass}>
                            <input
                                type="radio"
                                name="outcome"
                                value="forward"
                                checked={form.data.outcome === 'forward'}
                                onChange={() => form.setData('outcome', 'forward')}
                                className={checkboxClass}
                            />

                            <span>Record recommendation and forward to District Secretariat</span>
                        </label>

                        <label className={labelClass}>
                            <input
                                type="radio"
                                name="outcome"
                                value="returned"
                                checked={form.data.outcome === 'returned'}
                                onChange={() => form.setData('outcome', 'returned')}
                                className={checkboxClass}
                            />

                            <span>Return to employee for correction</span>
                        </label>
                    </div>

                    {form.errors.outcome && (
                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                            {form.errors.outcome}
                        </p>
                    )}
                </fieldset>

                {form.data.outcome === 'forward' && (
                    <>
                        <fieldset>
                            <legend className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                Recommendation <span className="text-red-600">*</span>
                            </legend>

                            <div className="mt-2 space-y-2">
                                <label className={labelClass}>
                                    <input
                                        type="radio"
                                        name="recommendation"
                                        value="recommended"
                                        checked={form.data.recommendation === 'recommended'}
                                        onChange={() =>
                                            form.setData(
                                                'recommendation',
                                                'recommended',
                                            )
                                        }
                                        className={checkboxClass}
                                        required
                                    />

                                    <span>Recommended for transfer</span>
                                </label>

                                <label className={labelClass}>
                                    <input
                                        type="radio"
                                        name="recommendation"
                                        value="not_recommended"
                                        checked={form.data.recommendation === 'not_recommended'}
                                        onChange={() =>
                                            form.setData(
                                                'recommendation',
                                                'not_recommended',
                                            )
                                        }
                                        className={checkboxClass}
                                        required
                                    />

                                    <span>Not recommended for transfer</span>
                                </label>
                            </div>

                            {form.errors.recommendation && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {form.errors.recommendation}
                                </p>
                            )}
                        </fieldset>

                        <fieldset>
                            <legend className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                Applicable circumstances
                            </legend>

                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Select all that apply.
                            </p>

                            <div className="mt-3 space-y-2">
                                <label className={labelClass}>
                                    <input
                                        type="checkbox"
                                        checked={form.data.insufficient_service}
                                        onChange={(event) =>
                                            form.setData(
                                                'insufficient_service',
                                                event.target.checked,
                                            )
                                        }
                                        className={checkboxClass}
                                    />

                                    <span>
                                        Required period of service at the current office has
                                        not been completed
                                    </span>
                                </label>

                                <label className={labelClass}>
                                    <input
                                        type="checkbox"
                                        checked={form.data.replacement_required}
                                        onChange={(event) =>
                                            form.setData(
                                                'replacement_required',
                                                event.target.checked,
                                            )
                                        }
                                        className={checkboxClass}
                                    />

                                    <span>Replacement is required before release</span>
                                </label>

                                <label className={labelClass}>
                                    <input
                                        type="checkbox"
                                        checked={form.data.pending_matters}
                                        onChange={(event) =>
                                            form.setData(
                                                'pending_matters',
                                                event.target.checked,
                                            )
                                        }
                                        className={checkboxClass}
                                    />

                                    <span>
                                        There are pending disciplinary or administrative matters
                                    </span>
                                </label>

                                <label className={labelClass}>
                                    <input
                                        type="checkbox"
                                        checked={form.data.other_circumstances}
                                        onChange={(event) =>
                                            form.setData(
                                                'other_circumstances',
                                                event.target.checked,
                                            )
                                        }
                                        className={checkboxClass}
                                    />

                                    <span>Other relevant circumstances apply</span>
                                </label>
                            </div>
                        </fieldset>

                        <div>
                            <label className={labelClass}>
                                <input
                                    type="checkbox"
                                    checked={form.data.signed_paper_verified}
                                    onChange={(event) =>
                                        form.setData(
                                            'signed_paper_verified',
                                            event.target.checked,
                                        )
                                    }
                                    className={checkboxClass}
                                    required
                                />

                                <span>
                                    I have checked the signed paper application.{' '}
                                    <span className="text-red-600">*</span>
                                </span>
                            </label>

                            {form.errors.signed_paper_verified && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {form.errors.signed_paper_verified}
                                </p>
                            )}
                        </div>
                    </>
                )}

                <div>
                    <label
                        htmlFor="office-review-remarks"
                        className="block text-sm font-medium text-gray-900 dark:text-gray-100"
                    >
                        {form.data.outcome === 'returned'
                            ? 'Corrections required'
                            : 'Remarks'}

                        {form.data.outcome === 'returned' && (
                            <span className="text-red-600"> *</span>
                        )}
                    </label>

                    <textarea
                        id="office-review-remarks"
                        value={form.data.remarks}
                        onChange={(event) =>
                            form.setData('remarks', event.target.value)
                        }
                        rows={4}
                        maxLength={5000}
                        required={form.data.outcome === 'returned'}
                        className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    />

                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {form.data.outcome === 'returned'
                            ? 'Explain clearly what the employee should correct.'
                            : 'Optional. Add information relevant to the recommendation.'}
                    </p>

                    {form.errors.remarks && (
                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                            {form.errors.remarks}
                        </p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    className="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {form.processing
                        ? 'Saving...'
                        : form.data.outcome === 'forward'
                          ? 'Forward Recommendation'
                          : 'Return for Correction'}
                </button>
            </form>
        </section>
    );
}
