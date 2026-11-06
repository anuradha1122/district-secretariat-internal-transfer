import { Head, Link, useForm } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    CircleCheck,
    CircleOff,
    Pencil,
    Plus,
    X,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type Service = {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
};

type Props = {
    services: Service[];
    canManage: boolean;
};

type ServiceForm = {
    code: string;
    name: string;
    is_active: boolean;
};

const emptyForm: ServiceForm = {
    code: '',
    name: '',
    is_active: true,
};

const labelClass =
    'block text-sm font-medium text-slate-700 dark:text-zinc-200';

const inputClass =
    'mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500';

const errorClass = 'mt-1.5 text-sm text-red-600 dark:text-red-400';

export default function ServicesIndex({ services, canManage }: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<ServiceForm>(emptyForm);

    const activeCount = services.filter((service) => service.is_active).length;
    const inactiveCount = services.length - activeCount;

    const resetForm = () => {
        setEditingId(null);
        form.clearErrors();
        form.setData({ ...emptyForm });
    };

    const startEdit = (service: Service) => {
        setEditingId(service.id);
        form.clearErrors();

        form.setData({
            code: service.code,
            name: service.name,
            is_active: service.is_active,
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
            form.put(`/services/${editingId}`, options);
        } else {
            form.post('/services', options);
        }
    };

    return (
        <>
            <Head title="Service Management" />

            <div className="min-h-full bg-slate-50 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
                <div className="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
                    {/* Heading */}
                    <div>
                        <div className="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                            <Link
                                href="/dashboard"
                                className="hover:text-slate-900 dark:hover:text-white"
                            >
                                Dashboard
                            </Link>

                            <span>/</span>

                            <span>Services</span>
                        </div>

                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Service Management
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Manage the services used for employee records and
                            transfer applications.
                        </p>
                    </div>

                    {/* Summary */}
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-600 dark:text-zinc-400">
                                    Total services
                                </p>

                                <BriefcaseBusiness className="h-5 w-5 text-slate-400 dark:text-zinc-500" />
                            </div>

                            <p className="mt-3 text-2xl font-semibold">
                                {services.length}
                            </p>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-600 dark:text-zinc-400">
                                    Active services
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
                                    Inactive services
                                </p>

                                <CircleOff className="h-5 w-5 text-slate-400 dark:text-zinc-500" />
                            </div>

                            <p className="mt-3 text-2xl font-semibold">
                                {inactiveCount}
                            </p>
                        </div>
                    </div>

                    {/* Form */}
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
                                                ? 'Edit service'
                                                : 'Add a new service'}
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                            Enter the service details below.
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
                                    <div>
                                        <label
                                            htmlFor="code"
                                            className={labelClass}
                                        >
                                            Service code
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
                                            placeholder="Example: DO"
                                            maxLength={30}
                                            className={inputClass}
                                            required
                                        />

                                        <p className="mt-1.5 text-xs text-slate-500 dark:text-zinc-400">
                                            Use a unique code for this service.
                                        </p>

                                        {form.errors.code && (
                                            <p className={errorClass}>
                                                {form.errors.code}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="name"
                                            className={labelClass}
                                        >
                                            Service name
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
                                            placeholder="Example: Development Officer"
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
                                </div>

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
                                                Active service
                                            </label>

                                            <p className="mt-1 text-xs leading-5 text-slate-600 dark:text-zinc-400">
                                                Active services can be selected
                                                for new employee records.
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
                                        className="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {editingId !== null ? (
                                            <Pencil className="h-4 w-4" />
                                        ) : (
                                            <Plus className="h-4 w-4" />
                                        )}

                                        {form.processing
                                            ? 'Saving...'
                                            : editingId !== null
                                              ? 'Update service'
                                              : 'Save service'}
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

                    {/* List */}
                    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Service directory
                                </h2>

                                <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                    View and manage the services in the system.
                                </p>
                            </div>

                            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                {services.length}{' '}
                                {services.length === 1 ? 'service' : 'services'}
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[600px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600 dark:bg-zinc-950 dark:text-zinc-400">
                                    <tr>
                                        <th className="px-5 py-3.5 font-medium sm:px-6">
                                            Service
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
                                    {services.map((service) => (
                                        <tr
                                            key={service.id}
                                            className="hover:bg-slate-50 dark:hover:bg-zinc-800/50"
                                        >
                                            <td className="px-5 py-4 sm:px-6">
                                                <div className="font-medium text-slate-900 dark:text-zinc-100">
                                                    {service.name}
                                                </div>

                                                <div className="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                                    {service.code}
                                                </div>
                                            </td>

                                            <td className="px-5 py-4">
                                                {service.is_active ? (
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
                                                            startEdit(service)
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

                                    {services.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canManage ? 3 : 2}
                                                className="px-6 py-12 text-center"
                                            >
                                                <BriefcaseBusiness className="mx-auto h-8 w-8 text-slate-400 dark:text-zinc-500" />

                                                <p className="mt-3 font-medium text-slate-900 dark:text-zinc-100">
                                                    No services added yet
                                                </p>

                                                <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                                    Add a service to get
                                                    started.
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
