import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

type Option = {
    id: number;
    name: string;
    code?: string;
};

type ReportRow = {
    application_id: number;
    reference_no: string | null;
    full_name: string | null;
    cycle_name: string | null;
    service_name: string | null;
    current_office_name: string | null;
    status: string;
    post?: string | null;
    grade?: string | null;
    submitted_at?: string | null;
    destination_office_name?: string | null;
    priority?: number | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    report: 'applications' | 'preferences';
    title: string;

    filters: {
        cycle_id: number | null;
        service_id: number | null;
        office_id: number | null;
        status: string | null;
    };

    cycles: Option[];
    services: Option[];
    offices: Option[];

    rows: {
        data: ReportRow[];
        links: PaginationLink[];
        total: number;
    };

    canExport: boolean;
};

export default function ReportsIndex({
    report,
    title,
    filters,
    cycles,
    services,
    offices,
    rows,
    canExport,
}: Props) {
    const [selectedReport, setSelectedReport] = useState(report);

    const [cycleId, setCycleId] = useState(
        filters.cycle_id ? String(filters.cycle_id) : '',
    );

    const [serviceId, setServiceId] = useState(
        filters.service_id ? String(filters.service_id) : '',
    );

    const [officeId, setOfficeId] = useState(
        filters.office_id ? String(filters.office_id) : '',
    );

    const [status, setStatus] = useState(filters.status ?? '');

    function applyFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        router.get(
            '/reports',
            {
                report: selectedReport,
                cycle_id: cycleId || undefined,
                service_id: serviceId || undefined,
                office_id: officeId || undefined,
                status: status || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    }

    /*
     * Export the currently displayed filters, not unsaved changes
     * in the form.
     */
    const exportParams = new URLSearchParams();

    exportParams.set('report', report);

    if (filters.cycle_id) {
        exportParams.set('cycle_id', String(filters.cycle_id));
    }

    if (filters.service_id) {
        exportParams.set('service_id', String(filters.service_id));
    }

    if (filters.office_id) {
        exportParams.set('office_id', String(filters.office_id));
    }

    if (filters.status) {
        exportParams.set('status', filters.status);
    }

    const selectClass =
        'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100';

    return (
        <>
            <Head title="Management Reports" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Management Reports
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        View applications and office preferences.
                    </p>
                </div>

                <form
                    onSubmit={applyFilters}
                    className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label
                                htmlFor="report"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Report
                            </label>

                            <select
                                id="report"
                                value={selectedReport}
                                onChange={(event) =>
                                    setSelectedReport(
                                        event.target.value as
                                            | 'applications'
                                            | 'preferences',
                                    )
                                }
                                className={selectClass}
                            >
                                <option value="applications">
                                    Service-wise applications
                                </option>

                                <option value="preferences">
                                    Office-to-office preferences
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                htmlFor="cycle"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Cycle
                            </label>

                            <select
                                id="cycle"
                                value={cycleId}
                                onChange={(event) =>
                                    setCycleId(event.target.value)
                                }
                                className={selectClass}
                            >
                                <option value="">All cycles</option>

                                {cycles.map((cycle) => (
                                    <option key={cycle.id} value={cycle.id}>
                                        {cycle.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label
                                htmlFor="service"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Service
                            </label>

                            <select
                                id="service"
                                value={serviceId}
                                onChange={(event) =>
                                    setServiceId(event.target.value)
                                }
                                className={selectClass}
                            >
                                <option value="">All services</option>

                                {services.map((service) => (
                                    <option key={service.id} value={service.id}>
                                        {service.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label
                                htmlFor="office"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Current office
                            </label>

                            <select
                                id="office"
                                value={officeId}
                                onChange={(event) =>
                                    setOfficeId(event.target.value)
                                }
                                className={selectClass}
                            >
                                <option value="">All offices</option>

                                {offices.map((office) => (
                                    <option key={office.id} value={office.id}>
                                        {office.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label
                                htmlFor="status"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Status
                            </label>

                            <select
                                id="status"
                                value={status}
                                onChange={(event) =>
                                    setStatus(event.target.value)
                                }
                                className={selectClass}
                            >
                                <option value="">All included statuses</option>
                                <option value="submitted">Submitted</option>
                                <option value="office_recommended">
                                    Forwarded for district review
                                </option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-3">
                        <button
                            type="submit"
                            className="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                        >
                            Apply Filters
                        </button>

                        <Link
                            href="/reports"
                            className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Clear
                        </Link>

                        {canExport && (
                            <>
                                <a
                                    href={`/reports/export/csv?${exportParams.toString()}`}
                                    className="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800"
                                >
                                    Export CSV for Excel
                                </a>

                                <a
                                    href={`/reports/export/pdf?${exportParams.toString()}`}
                                    className="rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                >
                                    Export PDF
                                </a>
                            </>
                        )}
                    </div>
                </form>

                <section className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 className="font-semibold text-gray-900 dark:text-gray-100">
                            {title}
                        </h2>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Total rows: {rows.total}
                            {report === 'preferences' &&
                                ' — one application can appear in multiple rows.'}
                        </p>
                    </div>

                    {rows.data.length === 0 ? (
                        <p className="p-5 text-sm text-gray-600 dark:text-gray-400">
                            No matching records were found.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    <tr>
                                        <th className="px-4 py-3">Reference</th>
                                        <th className="px-4 py-3">Applicant</th>
                                        <th className="px-4 py-3">Cycle</th>
                                        <th className="px-4 py-3">Service</th>
                                        <th className="px-4 py-3">Current office</th>

                                        {report === 'preferences' ? (
                                            <>
                                                <th className="px-4 py-3">
                                                    Preferred office
                                                </th>
                                                <th className="px-4 py-3">
                                                    Priority
                                                </th>
                                            </>
                                        ) : (
                                            <>
                                                <th className="px-4 py-3">Post</th>
                                                <th className="px-4 py-3">Grade</th>
                                            </>
                                        )}

                                        <th className="px-4 py-3">Status</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200 text-gray-900 dark:divide-gray-700 dark:text-gray-100">
                                    {rows.data.map((row, index) => (
                                        <tr
                                            key={`${row.application_id}-${row.priority ?? index}`}
                                        >
                                            <td className="px-4 py-3">
                                                {row.reference_no ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.full_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.cycle_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.service_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.current_office_name ?? '—'}
                                            </td>

                                            {report === 'preferences' ? (
                                                <>
                                                    <td className="px-4 py-3">
                                                        {row.destination_office_name ?? '—'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {row.priority ?? '—'}
                                                    </td>
                                                </>
                                            ) : (
                                                <>
                                                    <td className="px-4 py-3">
                                                        {row.post ?? '—'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {row.grade ?? '—'}
                                                    </td>
                                                </>
                                            )}

                                            <td className="px-4 py-3">
                                                {row.status.replaceAll('_', ' ')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {rows.links.length > 3 && (
                        <div className="flex flex-wrap gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                            {rows.links.map((link, index) => {
                                const label = link.label
                                    .replace('&laquo;', '«')
                                    .replace('&raquo;', '»');

                                return link.url ? (
                                    <Link
                                        key={index}
                                        href={link.url}
                                        className={`rounded border px-3 py-1 text-sm ${
                                            link.active
                                                ? 'border-blue-700 bg-blue-700 text-white'
                                                : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-200'
                                        }`}
                                    >
                                        {label}
                                    </Link>
                                ) : (
                                    <span
                                        key={index}
                                        className="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400 dark:border-gray-700"
                                    >
                                        {label}
                                    </span>
                                );
                            })}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}