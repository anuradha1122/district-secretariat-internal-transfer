import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type ActivationCode = {
    expires_at: string;
    used_at: string | null;
};

type Employee = {
    id: number;
    nic: string;
    is_active: boolean;
    user_exists: boolean;
    activation_code: ActivationCode | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    employees: {
        data: Employee[];
        total: number;
        links: PaginationLink[];
    };
    filters: {
        search: string;
    };
    issuedCode: {
        nic: string;
        code: string;
    } | null;
};

export default function ActivationCodesIndex({
    employees,
    filters,
    issuedCode,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const { errors } = usePage().props;

    const submitSearch: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        router.get(
            '/activation-codes',
            { search },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const issueCode = (employee: Employee) => {
        if (
            !window.confirm(
                'Have you verified this employee’s identity? Issuing a new code replaces any previous unused code.',
            )
        ) {
            return;
        }

        router.post(`/activation-codes/${employee.id}`);
    };

    const revokeCode = (employee: Employee) => {
        if (!window.confirm('Revoke the unused activation code?')) {
            return;
        }

        router.delete(`/activation-codes/${employee.id}`);
    };

    return (
        <>
            <Head title="Activation Codes" />

            <div className="min-h-full bg-slate-50 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
                <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
                    <div>
                        <Link
                            href="/eligible-employees"
                            className="text-sm text-blue-700 hover:underline dark:text-blue-400"
                        >
                            ← Eligible Employees
                        </Link>

                        <h1 className="mt-3 text-2xl font-semibold">
                            Employee Activation Codes
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Verify the employee’s identity before issuing a
                            code. Deliver the code privately.
                        </p>
                    </div>

                    {issuedCode && (
                        <div
                            role="status"
                            className="rounded-xl border border-emerald-300 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"
                        >
                            <p className="font-semibold">
                                Activation code issued
                            </p>

                            <p className="mt-2 text-sm">
                                NIC: <strong>{issuedCode.nic}</strong>
                            </p>

                            <p className="mt-2 break-all font-mono text-lg font-bold">
                                {issuedCode.code}
                            </p>

                            <p className="mt-3 text-sm leading-6">
                                This code is displayed once and expires in
                                24 hours. Give it privately to the verified
                                employee. Refreshing this page will remove it.
                            </p>
                        </div>
                    )}

                    {errors.activation && (
                        <div
                            role="alert"
                            className="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                        >
                            {errors.activation}
                        </div>
                    )}

                    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 p-5 dark:border-zinc-800">
                            <div>
                                <h2 className="font-semibold">
                                    Eligible employees
                                </h2>

                                <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                    {employees.total} matching record(s)
                                </p>
                            </div>

                            <form
                                onSubmit={submitSearch}
                                className="flex gap-2"
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
                                    className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                />

                                <button
                                    type="submit"
                                    className="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-zinc-700"
                                >
                                    Search
                                </button>
                            </form>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[650px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-zinc-950 dark:text-zinc-400">
                                    <tr>
                                        <th className="px-5 py-3">NIC</th>
                                        <th className="px-5 py-3">Eligibility</th>
                                        <th className="px-5 py-3">Account</th>
                                        <th className="px-5 py-3">Code</th>
                                        <th className="px-5 py-3 text-right">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100 dark:divide-zinc-800">
                                    {employees.data.map((employee) => (
                                        <tr key={employee.id}>
                                            <td className="px-5 py-4 font-medium">
                                                {employee.nic}
                                            </td>

                                            <td className="px-5 py-4">
                                                {employee.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </td>

                                            <td className="px-5 py-4">
                                                {employee.user_exists
                                                    ? 'Created'
                                                    : 'Not created'}
                                            </td>

                                            <td className="px-5 py-4">
                                                {employee.activation_code
                                                    ? employee.activation_code
                                                          .used_at
                                                        ? 'Used'
                                                        : 'Issued'
                                                    : 'Not issued'}
                                            </td>

                                            <td className="px-5 py-4 text-right">
                                                {!employee.user_exists &&
                                                    employee.is_active && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                issueCode(
                                                                    employee,
                                                                )
                                                            }
                                                            className="font-medium text-blue-700 hover:underline dark:text-blue-400"
                                                        >
                                                            {employee.activation_code
                                                                ? 'Issue new code'
                                                                : 'Issue code'}
                                                        </button>
                                                    )}

                                                {!employee.user_exists &&
                                                    employee.activation_code &&
                                                    !employee.activation_code
                                                        .used_at && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                revokeCode(
                                                                    employee,
                                                                )
                                                            }
                                                            className="ml-4 font-medium text-red-700 hover:underline dark:text-red-400"
                                                        >
                                                            Revoke
                                                        </button>
                                                    )}
                                            </td>
                                        </tr>
                                    ))}

                                    {employees.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-5 py-10 text-center text-slate-500 dark:text-zinc-400"
                                            >
                                                No eligible employees found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {employees.links.length > 3 && (
                            <div className="flex flex-wrap gap-2 border-t border-slate-200 p-5 dark:border-zinc-800">
                                {employees.links.map((link, index) => {
                                    const label = link.label
                                        .replace('&laquo;', '«')
                                        .replace('&raquo;', '»');

                                    return link.url ? (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            className={
                                                link.active
                                                    ? 'rounded bg-blue-700 px-3 py-1.5 text-xs text-white'
                                                    : 'rounded border border-slate-300 px-3 py-1.5 text-xs dark:border-zinc-700'
                                            }
                                        >
                                            {label}
                                        </Link>
                                    ) : (
                                        <span
                                            key={index}
                                            className="rounded border border-slate-200 px-3 py-1.5 text-xs text-slate-400 dark:border-zinc-800"
                                        >
                                            {label}
                                        </span>
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
