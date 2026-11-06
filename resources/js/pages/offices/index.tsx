import { FormEventHandler, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    Building2,
    CircleCheck,
    CircleOff,
    MapPin,
    Pencil,
    Plus,
    X,
} from 'lucide-react';

type OfficeType = 'district_secretariat' | 'divisional_secretariat';

type Office = {
    id: number;
    code: string;
    name: string;
    type: OfficeType;
    parent_id: number | null;
    is_active: boolean;
    parent: {
        id: number;
        name: string;
    } | null;
};

type DistrictOffice = {
    id: number;
    name: string;
};

type Props = {
    offices: Office[];
    districtOffices: DistrictOffice[];
    canManage: boolean;
};

type OfficeForm = {
    code: string;
    name: string;
    type: OfficeType;
    parent_id: string;
    is_active: boolean;
};

const emptyForm: OfficeForm = {
    code: '',
    name: '',
    type: 'district_secretariat',
    parent_id: '',
    is_active: true,
};

const inputClass =
    'mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500';

const labelClass =
    'block text-sm font-medium text-slate-700 dark:text-zinc-200';

const errorClass = 'mt-1.5 text-sm text-red-600 dark:text-red-400';

export default function OfficesIndex({
    offices,
    districtOffices,
    canManage,
}: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<OfficeForm>(emptyForm);

    const activeCount = offices.filter((office) => office.is_active).length;

    const divisionCount = offices.filter(
        (office) => office.type === 'divisional_secretariat',
    ).length;

    const resetForm = () => {
        setEditingId(null);
        form.clearErrors();
        form.setData({ ...emptyForm });
    };

    const startEdit = (office: Office) => {
        setEditingId(office.id);
        form.clearErrors();

        form.setData({
            code: office.code,
            name: office.name,
            type: office.type,
            parent_id: office.parent_id ? String(office.parent_id) : '',
            is_active: office.is_active,
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
            form.put(`/offices/${editingId}`, options);
        } else {
            form.post('/offices', options);
        }
    };

    return (
        <>
            <Head title="Office Management" />

            <div className="min-h-full bg-slate-50 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
                <div className="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
                    {/* Page heading */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                <Link
                                    href="/dashboard"
                                    className="hover:text-slate-900 dark:hover:text-white"
                                >
                                    Dashboard
                                </Link>

                                <span>/</span>

                                <span>Offices</span>
                            </div>

                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                Office Management
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-zinc-400">
                                Manage the District Secretariat and its
                                Divisional Secretariats.
                            </p>
                        </div>
                    </div>

                    {/* Summary cards */}
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-600 dark:text-zinc-400">
                                    Total offices
                                </p>

                                <Building2 className="h-5 w-5 text-slate-400 dark:text-zinc-500" />
                            </div>

                            <p className="mt-3 text-2xl font-semibold">
                                {offices.length}
                            </p>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-600 dark:text-zinc-400">
                                    Active offices
                                </p>

                                <CircleCheck className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>

                            <p className="mt-3 text-2xl font-semibold">
                                {activeCount}
                            </p>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-600 dark:text-zinc-400">
                                    Divisional Secretariats
                                </p>

                                <MapPin className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>

                            <p className="mt-3 text-2xl font-semibold">
                                {divisionCount}
                            </p>
                        </div>
                    </div>

                    {/* Create / edit form */}
                    {canManage && (
                        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                                <div className="flex gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                                        {editingId !== null ? (
                                            <Pencil className="h-5 w-5" />
                                        ) : (
                                            <Plus className="h-5 w-5" />
                                        )}
                                    </div>

                                    <div>
                                        <h2 className="text-base font-semibold">
                                            {editingId !== null
                                                ? 'Edit office'
                                                : 'Add a new office'}
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                            {editingId !== null
                                                ? 'Update the office information below.'
                                                : 'Enter the office details to add it to the system.'}
                                        </p>
                                    </div>
                                </div>

                                {editingId !== null && (
                                    <button
                                        type="button"
                                        onClick={resetForm}
                                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        <X className="h-4 w-4" />
                                        Cancel
                                    </button>
                                )}
                            </div>

                            <form onSubmit={submit} className="p-5 sm:p-6">
                                <div className="grid gap-5 md:grid-cols-2">
                                    {/* Code */}
                                    <div>
                                        <label
                                            htmlFor="code"
                                            className={labelClass}
                                        >
                                            Office code
                                            <span className="ml-1 text-red-600 dark:text-red-400">
                                                *
                                            </span>
                                        </label>

                                        <input
                                            id="code"
                                            type="text"
                                            value={form.data.code}
                                            onChange={(event) =>
                                                form.setData(
                                                    'code',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Example: DS-RAT"
                                            maxLength={30}
                                            className={inputClass}
                                            required
                                        />

                                        <p className="mt-1.5 text-xs text-slate-500 dark:text-zinc-400">
                                            Use a unique code for this office.
                                        </p>

                                        {form.errors.code && (
                                            <p className={errorClass}>
                                                {form.errors.code}
                                            </p>
                                        )}
                                    </div>

                                    {/* Name */}
                                    <div>
                                        <label
                                            htmlFor="name"
                                            className={labelClass}
                                        >
                                            Office name
                                            <span className="ml-1 text-red-600 dark:text-red-400">
                                                *
                                            </span>
                                        </label>

                                        <input
                                            id="name"
                                            type="text"
                                            value={form.data.name}
                                            onChange={(event) =>
                                                form.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Example: Ratnapura District Secretariat"
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

                                    {/* Type */}
                                    <div>
                                        <label
                                            htmlFor="type"
                                            className={labelClass}
                                        >
                                            Office type
                                            <span className="ml-1 text-red-600 dark:text-red-400">
                                                *
                                            </span>
                                        </label>

                                        <select
                                            id="type"
                                            value={form.data.type}
                                            onChange={(event) => {
                                                const type = event.target
                                                    .value as OfficeType;

                                                form.setData({
                                                    ...form.data,
                                                    type,
                                                    parent_id: '',
                                                });
                                            }}
                                            className={inputClass}
                                            required
                                        >
                                            <option value="district_secretariat">
                                                District Secretariat
                                            </option>

                                            <option value="divisional_secretariat">
                                                Divisional Secretariat
                                            </option>
                                        </select>

                                        {form.errors.type && (
                                            <p className={errorClass}>
                                                {form.errors.type}
                                            </p>
                                        )}
                                    </div>

                                    {/* Parent office */}
                                    {form.data.type ===
                                        'divisional_secretariat' && (
                                        <div>
                                            <label
                                                htmlFor="parent_id"
                                                className={labelClass}
                                            >
                                                District Secretariat
                                                <span className="ml-1 text-red-600 dark:text-red-400">
                                                    *
                                                </span>
                                            </label>

                                            <select
                                                id="parent_id"
                                                value={form.data.parent_id}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'parent_id',
                                                        event.target.value,
                                                    )
                                                }
                                                className={inputClass}
                                                required
                                            >
                                                <option value="">
                                                    Select a district office
                                                </option>

                                                {districtOffices.map(
                                                    (office) => (
                                                        <option
                                                            key={office.id}
                                                            value={office.id}
                                                        >
                                                            {office.name}
                                                        </option>
                                                    ),
                                                )}
                                            </select>

                                            {form.errors.parent_id && (
                                                <p className={errorClass}>
                                                    {form.errors.parent_id}
                                                </p>
                                            )}

                                            {districtOffices.length === 0 && (
                                                <p className="mt-1.5 text-xs text-amber-700 dark:text-amber-400">
                                                    Add an active District
                                                    Secretariat first.
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Active status */}
                                <div className="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                                    <div className="flex items-start gap-3">
                                        <input
                                            id="is_active"
                                            type="checkbox"
                                            checked={form.data.is_active}
                                            onChange={(event) =>
                                                form.setData(
                                                    'is_active',
                                                    event.target.checked,
                                                )
                                            }
                                            className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800"
                                        />

                                        <div>
                                            <label
                                                htmlFor="is_active"
                                                className="text-sm font-medium text-slate-900 dark:text-zinc-100"
                                            >
                                                Active office
                                            </label>

                                            <p className="mt-1 text-xs leading-5 text-slate-600 dark:text-zinc-400">
                                                Active offices can be used in
                                                the system.
                                            </p>
                                        </div>
                                    </div>

                                    {form.errors.is_active && (
                                        <p className={errorClass}>
                                            {form.errors.is_active}
                                        </p>
                                    )}
                                </div>

                                <div className="mt-6 flex flex-wrap gap-3">
                                    <button
                                        type="submit"
                                        disabled={form.processing}
                                        className="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-zinc-900"
                                    >
                                        {editingId !== null ? (
                                            <Pencil className="h-4 w-4" />
                                        ) : (
                                            <Plus className="h-4 w-4" />
                                        )}

                                        {form.processing
                                            ? 'Saving...'
                                            : editingId !== null
                                              ? 'Update office'
                                              : 'Save office'}
                                    </button>

                                    {editingId !== null && (
                                        <button
                                            type="button"
                                            onClick={resetForm}
                                            className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            Cancel
                                        </button>
                                    )}
                                </div>
                            </form>
                        </section>
                    )}

                    {/* Offices list */}
                    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Office directory
                                </h2>

                                <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                    View and manage the offices added to the
                                    system.
                                </p>
                            </div>

                            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                {offices.length}{' '}
                                {offices.length === 1 ? 'office' : 'offices'}
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[720px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600 dark:bg-zinc-950 dark:text-zinc-400">
                                    <tr>
                                        <th className="px-5 py-3.5 font-medium sm:px-6">
                                            Office
                                        </th>

                                        <th className="px-5 py-3.5 font-medium">
                                            Type
                                        </th>

                                        <th className="px-5 py-3.5 font-medium">
                                            District office
                                        </th>

                                        <th className="px-5 py-3.5 font-medium">
                                            Status
                                        </th>

                                        {canManage && (
                                            <th className="px-5 py-3.5 text-right font-medium sm:px-6">
                                                Action
                                            </th>
                                        )}
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100 dark:divide-zinc-800">
                                    {offices.map((office) => (
                                        <tr
                                            key={office.id}
                                            className="hover:bg-slate-50 dark:hover:bg-zinc-800/50"
                                        >
                                            <td className="px-5 py-4 sm:px-6">
                                                <div className="font-medium text-slate-900 dark:text-zinc-100">
                                                    {office.name}
                                                </div>

                                                <div className="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                                    {office.code}
                                                </div>
                                            </td>

                                            <td className="px-5 py-4 text-slate-700 dark:text-zinc-300">
                                                {office.type ===
                                                'district_secretariat'
                                                    ? 'District Secretariat'
                                                    : 'Divisional Secretariat'}
                                            </td>

                                            <td className="px-5 py-4 text-slate-700 dark:text-zinc-300">
                                                {office.parent?.name ?? '—'}
                                            </td>

                                            <td className="px-5 py-4">
                                                {office.is_active ? (
                                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                                        <CircleCheck className="h-3.5 w-3.5" />
                                                        Active
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-zinc-800 dark:text-zinc-400">
                                                        <CircleOff className="h-3.5 w-3.5" />
                                                        Inactive
                                                    </span>
                                                )}
                                            </td>

                                            {canManage && (
                                                <td className="px-5 py-4 text-right sm:px-6">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            startEdit(office)
                                                        }
                                                        className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-500/10"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                        Edit
                                                    </button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}

                                    {offices.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canManage ? 5 : 4}
                                                className="px-6 py-12 text-center"
                                            >
                                                <Building2 className="mx-auto h-8 w-8 text-slate-400 dark:text-zinc-500" />

                                                <p className="mt-3 font-medium text-slate-900 dark:text-zinc-100">
                                                    No offices added yet
                                                </p>

                                                <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                                    Start by adding the
                                                    District Secretariat.
                                                </p>
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
