import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Cycle = {
    id: number;
    name: string;
};

type Application = {
    id: number;
    reference_no: string;
    status: string;
    cycle: Cycle;
};

type Props = {
    cycles: Cycle[];
    applications: Application[];
};

export default function TransferApplicationsIndex({
    cycles,
    applications,
}: Props) {
    const form = useForm({
        transfer_cycle_id: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        form.post('/transfer-applications');
    };

    return (
        <>
            <Head title="My Transfer Applications" />

            <div className="min-h-full bg-slate-50 p-4 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100 sm:p-6 lg:p-8">
                <div className="mx-auto max-w-5xl space-y-6">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            My Transfer Applications
                        </h1>

                        <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                            Start a new application or continue a saved draft.
                        </p>
                    </div>

                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 className="font-semibold">
                            Start an application
                        </h2>

                        {cycles.length === 0 ? (
                            <p className="mt-3 text-sm text-slate-600 dark:text-zinc-400">
                                There are no transfer cycles currently open.
                            </p>
                        ) : (
                            <form
                                onSubmit={submit}
                                className="mt-4 flex flex-wrap gap-3"
                            >
                                <label
                                    htmlFor="transfer_cycle_id"
                                    className="sr-only"
                                >
                                    Transfer cycle
                                </label>

                                <select
                                    id="transfer_cycle_id"
                                    value={form.data.transfer_cycle_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'transfer_cycle_id',
                                            event.target.value,
                                        )
                                    }
                                    required
                                    className="min-w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                >
                                    <option value="">
                                        Select a transfer cycle
                                    </option>

                                    {cycles.map((cycle) => (
                                        <option key={cycle.id} value={cycle.id}>
                                            {cycle.name}
                                        </option>
                                    ))}
                                </select>

                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60"
                                >
                                    Start application
                                </button>

                                {form.errors.transfer_cycle_id && (
                                    <p className="w-full text-sm text-red-600 dark:text-red-400">
                                        {form.errors.transfer_cycle_id}
                                    </p>
                                )}
                            </form>
                        )}
                    </section>

                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 className="font-semibold">
                            Your applications
                        </h2>

                        {applications.length === 0 ? (
                            <p className="mt-3 text-sm text-slate-600 dark:text-zinc-400">
                                You have not started an application yet.
                            </p>
                        ) : (
                            <div className="mt-4 space-y-3">
                                {applications.map((application) => (
                                    <div
                                        key={application.id}
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-4 dark:border-zinc-700"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {application.cycle.name}
                                            </p>

                                            <p className="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                                {application.reference_no}
                                            </p>

                                            <p className="mt-1 text-sm capitalize">
                                                {application.status}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-4">
                                            {application.status === 'draft' && (
                                                <Link
                                                    href={`/transfer-applications/${application.id}/edit`}
                                                    className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                                                >
                                                    Continue draft
                                                </Link>
                                            )}

                                            <Link
                                                href={`/transfer-applications/${application.id}/review`}
                                                className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                                            >
                                                {application.status === 'draft'
                                                    ? 'Review and submit'
                                                    : 'View application'}
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
