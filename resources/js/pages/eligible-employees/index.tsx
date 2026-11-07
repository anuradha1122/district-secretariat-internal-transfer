import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Download,
    FileUp,
    Search,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { FormEventHandler, useRef, useState } from 'react';

type EligibleEmployee = {
    id: number;
    nic: string;
    is_active: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedEmployees = {
    data: EligibleEmployee[];
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

type Props = {
    employees: PaginatedEmployees;
    filters: {
        search: string;
    };
    canImport: boolean;
    canManage: boolean;
};

type ImportForm = {
    file: File | null;
};

export default function EligibleEmployeesIndex({
    employees,
    filters,
    canImport,
    canManage,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const fileInput = useRef<HTMLInputElement>(null);

    const form = useForm<ImportForm>({
        file: null,
    });

    const submitImport: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        form.post('/eligible-employees/import', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.clearErrors();

                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    };

    const submitSearch: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        router.get(
            '/eligible-employees',
            { search },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const changeStatus = (employee: EligibleEmployee) => {
        const action = employee.is_active ? 'deactivate' : 'activate';

        if (!window.confirm(`Are you sure you want to ${action} this NIC?`)) {
            return;
        }

        router.patch(
            `/eligible-employees/${employee.id}/status`,
            {
                is_active: !employee.is_active,
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Eligible Employees" />

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

                            <span>Eligible Employees</span>
                        </div>

                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Eligible Employees
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Manage the NIC numbers permitted to begin account
                            activation.
                        </p>
                    </div>

                    {/* Information */}
                    <div className="flex gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200">
                        <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0" />

                        <div>
                            <p className="font-medium">
                                Eligibility does not create an account
                            </p>

                            <p className="mt-1 leading-6">
                                Imported employees will use a separate secure
                                activation process. An NIC by itself is not a
                                password.
                            </p>
                        </div>
                    </div>

                    {/* Import */}
                    {canImport && (
                        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                                <div className="flex gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                                        <FileUp className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <h2 className="text-base font-semibold">
                                            Import NIC numbers
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                            Upload a CSV file with one column
                                            named <strong>nic</strong>.
                                        </p>
                                    </div>
                                </div>

                                <a
                                    href="/eligible-employees/template"
                                    className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    <Download className="h-4 w-4" />
                                    Download template
                                </a>
                            </div>

                            <form
                                onSubmit={submitImport}
                                className="space-y-4 p-5 sm:p-6"
                            >
                                <div>
                                    <label
                                        htmlFor="file"
                                        className="block text-sm font-medium text-slate-700 dark:text-zinc-200"
                                    >
                                        CSV file
                                    </label>

                                    <input
                                        ref={fileInput}
                                        id="file"
                                        type="file"
                                        accept=".csv,text/csv"
                                        onChange={(event) =>
                                            form.setData(
                                                'file',
                                                event.target.files?.[0] ?? null,
                                            )
                                        }
                                        className="mt-2 block w-full rounded-lg border border-slate-300 bg-white p-2 text-sm text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                                        required
                                    />

                                    <p className="mt-2 text-xs leading-5 text-slate-500 dark:text-zinc-400">
                                        Maximum: 5,000 NICs and 2 MB per file.
                                        Format the NIC column as text in Excel
                                        to preserve leading zeroes.
                                    </p>

                                    {form.errors.file && (
                                        <p
                                            role="alert"
                                            className="mt-2 text-sm text-red-600 dark:text-red-400"
                                        >
                                            {form.errors.file}
                                        </p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={form.processing || !form.data.file}
                                    className="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <FileUp className="h-4 w-4" />

                                    {form.processing
                                        ? 'Importing...'
                                        : 'Import NICs'}
                                </button>
                            </form>
                        </section>
                    )}

                    {/* List */}
                    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    <Users className="h-5 w-5" />
                                </div>

                                <div>
                                    <h2 className="text-base font-semibold">
                                        Eligibility list
                                    </h2>

                                    <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                        {employees.total} matching record(s)
                                    </p>
                                </div>
                            </div>

                            <form
                                onSubmit={submitSearch}
                                className="flex w-full gap-2 sm:w-auto"
                            >
                                <label
                                    htmlFor="search"
                                    className="sr-only"
                                >
                                    Search NIC
                                </label>

                                <input
                                    id="search"
                                    type="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search NIC"
                                    className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-500 sm:w-56"
                                />

                                <button
                                    type="submit"
                                    aria-label="Search"
                                    className="rounded-lg border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    <Search className="h-4 w-4" />
                                </button>
                            </form>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[500px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600 dark:bg-zinc-950 dark:text-zinc-400">
                                    <tr>
                                        <th className="px-5 py-3.5 font-medium sm:px-6">
                                            NIC
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
                                    {employees.data.map((employee) => (
                                        <tr
                                            key={employee.id}
                                            className="hover:bg-slate-50 dark:hover:bg-zinc-800/50"
                                        >
                                            <td className="px-5 py-4 font-medium text-slate-900 dark:text-zinc-100 sm:px-6">
                                                {employee.nic}
                                            </td>

                                            <td className="px-5 py-4">
                                                <span
                                                    className={
                                                        employee.is_active
                                                            ? 'rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                            : 'rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                    }
                                                >
                                                    {employee.is_active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </span>
                                            </td>

                                            {canManage && (
                                                <td className="px-5 py-4 text-right sm:px-6">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            changeStatus(
                                                                employee,
                                                            )
                                                        }
                                                        className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                                                    >
                                                        {employee.is_active
                                                            ? 'Deactivate'
                                                            : 'Activate'}
                                                    </button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}

                                    {employees.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canManage ? 3 : 2}
                                                className="px-6 py-12 text-center text-sm text-slate-600 dark:text-zinc-400"
                                            >
                                                No eligible employees found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {employees.links.length > 3 && (
                            <div className="flex flex-wrap items-center gap-2 border-t border-slate-200 px-5 py-4 dark:border-zinc-800">
                                {employees.links.map((link, index) => {
                                    const label = link.label
                                        .replace('&laquo;', '«')
                                        .replace('&raquo;', '»');

                                    if (!link.url) {
                                        return (
                                            <span
                                                key={index}
                                                className="rounded border border-slate-200 px-3 py-1.5 text-xs text-slate-400 dark:border-zinc-800 dark:text-zinc-600"
                                            >
                                                {label}
                                            </span>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            className={
                                                link.active
                                                    ? 'rounded border border-blue-700 bg-blue-700 px-3 py-1.5 text-xs text-white'
                                                    : 'rounded border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800'
                                            }
                                        >
                                            {label}
                                        </Link>
                                    );
                                })}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
