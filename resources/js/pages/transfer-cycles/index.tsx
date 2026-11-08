import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarDays, Pencil, Plus, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type CycleStatus = 'draft' | 'open' | 'closed';

type TransferCycle = {
    id: number;
    name: string;
    opens_on: string;
    closes_on: string;
    status: CycleStatus;
    description: string | null;
};

type Props = {
    cycles: TransferCycle[];
    canManage: boolean;
};

type CycleForm = {
    name: string;
    opens_on: string;
    closes_on: string;
    status: CycleStatus;
    description: string;
};

const emptyForm: CycleForm = {
    name: '',
    opens_on: '',
    closes_on: '',
    status: 'draft',
    description: '',
};

const labelClass =
    'block text-sm font-medium text-slate-700 dark:text-zinc-200';

const inputClass =
    'mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-500';

const errorClass =
    'mt-1.5 text-sm text-red-600 dark:text-red-400';

const statusLabels: Record<CycleStatus, string> = {
    draft: 'Draft',
    open: 'Open',
    closed: 'Closed',
};

export default function TransferCyclesIndex({
    cycles,
    canManage,
}: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<CycleForm>(emptyForm);

    const resetForm = () => {
        setEditingId(null);
        form.clearErrors();
        form.setData({ ...emptyForm });
    };

    const editCycle = (cycle: TransferCycle) => {
        setEditingId(cycle.id);
        form.clearErrors();

        form.setData({
            name: cycle.name,
            opens_on: cycle.opens_on,
            closes_on: cycle.closes_on,
            status: cycle.status,
            description: cycle.description ?? '',
        });

        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    };

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: resetForm,
        };

        if (editingId !== null) {
            form.put(`/transfer-cycles/${editingId}`, options);
        } else {
            form.post('/transfer-cycles', options);
        }
    };

    return (
        <>
            <Head title="Transfer Cycles" />

            <div className="min-h-full bg-slate-50 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
                <div className="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
                    <div>
                        <Link
                            href="/dashboard"
                            className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                        >
                            ← Dashboard
                        </Link>

                        <h1 className="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">
                            Transfer Cycles
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Manage the periods during which transfer
                            applications can be submitted.
                        </p>
                    </div>

                    {canManage && (
                        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                                <div className="flex gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                                        <CalendarDays className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <h2 className="text-base font-semibold">
                                            {editingId !== null
                                                ? 'Edit transfer cycle'
                                                : 'Add transfer cycle'}
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                            Enter the application dates and
                                            cycle status.
                                        </p>
                                    </div>
                                </div>

                                {editingId !== null && (
                                    <button
                                        type="button"
                                        onClick={resetForm}
                                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-zinc-700"
                                    >
                                        <X className="h-4 w-4" />
                                        Cancel
                                    </button>
                                )}
                            </div>

                            <form onSubmit={submit} className="p-5 sm:p-6">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <div className="md:col-span-2">
                                        <label
                                            htmlFor="name"
                                            className={labelClass}
                                        >
                                            Cycle name *
                                        </label>

                                        <input
                                            id="name"
                                            value={form.data.name}
                                            onChange={(event) =>
                                                form.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Example: Annual Transfers 2027"
                                            maxLength={255}
                                            className={inputClass}
                                            required
                                        />

                                        {form.errors.name && (
                                            <p className={errorClass}>
                                                {form.errors.name}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="opens_on"
                                            className={labelClass}
                                        >
                                            Opening date *
                                        </label>

                                        <input
                                            id="opens_on"
                                            type="date"
                                            value={form.data.opens_on}
                                            onChange={(event) =>
                                                form.setData(
                                                    'opens_on',
                                                    event.target.value,
                                                )
                                            }
                                            className={inputClass}
                                            required
                                        />

                                        {form.errors.opens_on && (
                                            <p className={errorClass}>
                                                {form.errors.opens_on}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="closes_on"
                                            className={labelClass}
                                        >
                                            Closing date *
                                        </label>

                                        <input
                                            id="closes_on"
                                            type="date"
                                            value={form.data.closes_on}
                                            onChange={(event) =>
                                                form.setData(
                                                    'closes_on',
                                                    event.target.value,
                                                )
                                            }
                                            className={inputClass}
                                            required
                                        />

                                        {form.errors.closes_on && (
                                            <p className={errorClass}>
                                                {form.errors.closes_on}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="status"
                                            className={labelClass}
                                        >
                                            Status *
                                        </label>

                                        <select
                                            id="status"
                                            value={form.data.status}
                                            onChange={(event) =>
                                                form.setData(
                                                    'status',
                                                    event.target
                                                        .value as CycleStatus,
                                                )
                                            }
                                            className={inputClass}
                                        >
                                            <option value="draft">
                                                Draft
                                            </option>

                                            <option value="open">
                                                Open
                                            </option>

                                            <option value="closed">
                                                Closed
                                            </option>
                                        </select>

                                        {form.errors.status && (
                                            <p className={errorClass}>
                                                {form.errors.status}
                                            </p>
                                        )}

                                        <p className="mt-2 text-xs leading-5 text-slate-500 dark:text-zinc-400">
                                            An open cycle will accept
                                            applications only between its
                                            opening and closing dates.
                                        </p>
                                    </div>

                                    <div className="md:col-span-2">
                                        <label
                                            htmlFor="description"
                                            className={labelClass}
                                        >
                                            Description
                                        </label>

                                        <textarea
                                            id="description"
                                            value={form.data.description}
                                            onChange={(event) =>
                                                form.setData(
                                                    'description',
                                                    event.target.value,
                                                )
                                            }
                                            rows={3}
                                            maxLength={5000}
                                            placeholder="Optional information for this cycle"
                                            className={inputClass}
                                        />

                                        {form.errors.description && (
                                            <p className={errorClass}>
                                                {form.errors.description}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-6 flex gap-3">
                                    <button
                                        type="submit"
                                        disabled={form.processing}
                                        className="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60"
                                    >
                                        {editingId !== null ? (
                                            <Pencil className="h-4 w-4" />
                                        ) : (
                                            <Plus className="h-4 w-4" />
                                        )}

                                        {form.processing
                                            ? 'Saving...'
                                            : editingId !== null
                                              ? 'Update cycle'
                                              : 'Save cycle'}
                                    </button>

                                    {editingId !== null && (
                                        <button
                                            type="button"
                                            onClick={resetForm}
                                            className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-700"
                                        >
                                            Cancel
                                        </button>
                                    )}
                                </div>
                            </form>
                        </section>
                    )}

                    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                            <h2 className="text-base font-semibold">
                                Transfer cycle list
                            </h2>

                            <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                {cycles.length}{' '}
                                {cycles.length === 1 ? 'cycle' : 'cycles'}
                            </p>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[650px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600 dark:bg-zinc-950 dark:text-zinc-400">
                                    <tr>
                                        <th className="px-5 py-3.5">
                                            Cycle
                                        </th>

                                        <th className="px-5 py-3.5">
                                            Opens
                                        </th>

                                        <th className="px-5 py-3.5">
                                            Closes
                                        </th>

                                        <th className="px-5 py-3.5">
                                            Status
                                        </th>

                                        {canManage && (
                                            <th className="px-5 py-3.5 text-right">
                                                Action
                                            </th>
                                        )}
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100 dark:divide-zinc-800">
                                    {cycles.map((cycle) => (
                                        <tr key={cycle.id}>
                                            <td className="px-5 py-4">
                                                <div className="font-medium">
                                                    {cycle.name}
                                                </div>

                                                {cycle.description && (
                                                    <div className="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                                        {cycle.description}
                                                    </div>
                                                )}
                                            </td>

                                            <td className="px-5 py-4">
                                                {cycle.opens_on}
                                            </td>

                                            <td className="px-5 py-4">
                                                {cycle.closes_on}
                                            </td>

                                            <td className="px-5 py-4">
                                                {statusLabels[cycle.status]}
                                            </td>

                                            {canManage && (
                                                <td className="px-5 py-4 text-right">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            editCycle(cycle)
                                                        }
                                                        className="font-medium text-blue-700 hover:underline dark:text-blue-400"
                                                    >
                                                        Edit
                                                    </button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}

                                    {cycles.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canManage ? 5 : 4}
                                                className="px-5 py-10 text-center text-slate-500 dark:text-zinc-400"
                                            >
                                                No transfer cycles have been
                                                created yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
