import { Head, Link } from '@inertiajs/react';

type Office = {
    id: number;
    code: string;
    name: string;
};

type Service = {
    id: number;
    code: string;
    name: string;
};

type Cycle = {
    id: number;
    name: string;
};

type Application = {
    id: number;
    reference_no: string | null;
    full_name: string | null;
    post: string | null;
    grade: string | null;
    submitted_at: string | null;
    office_recommendation: string | null;
    office_decided_at: string | null;
    current_office: Office | null;
    service: Service | null;
    cycle: Cycle | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    applications: {
        data: Application[];
        links: PaginationLink[];
        total: number;
    };
};

function recommendationLabel(value: string | null): string {
    if (value === 'recommended') {
        return 'Recommended';
    }

    if (value === 'not_recommended') {
        return 'Not recommended';
    }

    return 'Not available';
}

export default function DistrictReviewIndex({ applications }: Props) {
    return (
        <>
            <Head title="District Review Inbox" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        District Review Inbox
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Applications forwarded by Office Heads for district review.
                    </p>
                </div>

                <div className="rounded-md bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                    This inbox currently shows forwarded applications. The detailed
                    district review and decision form will be added in the next step.
                </div>

                <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 className="font-semibold text-gray-900 dark:text-gray-100">
                            Applications awaiting district review
                        </h2>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Total: {applications.total}
                        </p>
                    </div>

                    {applications.data.length === 0 ? (
                        <p className="p-5 text-sm text-gray-600 dark:text-gray-400">
                            There are no applications available for your district review.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    <tr>
                                        <th className="px-4 py-3">Reference</th>
                                        <th className="px-4 py-3">Applicant</th>
                                        <th className="px-4 py-3">Current office</th>
                                        <th className="px-4 py-3">Service</th>
                                        <th className="px-4 py-3">Cycle</th>
                                        <th className="px-4 py-3">Office recommendation</th>
                                        <th className="px-4 py-3">Forwarded</th>
                                        <th className="px-4 py-3">Action</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200 text-gray-900 dark:divide-gray-700 dark:text-gray-100">
                                    {applications.data.map((application) => (
                                        <tr key={application.id}>
                                            <td className="px-4 py-3">
                                                {application.reference_no ?? '—'}
                                            </td>

                                            <td className="px-4 py-3">
                                                <div>{application.full_name ?? '—'}</div>

                                                <div className="text-xs text-gray-500 dark:text-gray-400">
                                                    {[application.post, application.grade]
                                                        .filter(Boolean)
                                                        .join(' · ') || '—'}
                                                </div>
                                            </td>

                                            <td className="px-4 py-3">
                                                {application.current_office?.name ?? '—'}
                                            </td>

                                            <td className="px-4 py-3">
                                                {application.service?.name ?? '—'}
                                            </td>

                                            <td className="px-4 py-3">
                                                {application.cycle?.name ?? '—'}
                                            </td>

                                            <td className="px-4 py-3">
                                                <span
                                                    className={
                                                        application.office_recommendation ===
                                                        'not_recommended'
                                                            ? 'rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200'
                                                            : 'rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-200'
                                                    }
                                                >
                                                    {recommendationLabel(
                                                        application.office_recommendation,
                                                    )}
                                                </span>
                                            </td>

                                            <td className="px-4 py-3">
                                                {application.office_decided_at ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/district-reviews/${application.id}`}
                                                    className="font-medium text-blue-700 hover:underline dark:text-blue-400"
                                                >
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {applications.links.length > 3 && (
                        <div className="flex flex-wrap gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                            {applications.links.map((link, index) => {
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
                </div>
            </div>
        </>
    );
}
