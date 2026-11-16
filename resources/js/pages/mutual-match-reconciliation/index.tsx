import { Head, Link } from '@inertiajs/react';

type Item = {
    decision_id: number;
    run_id: number;
    decided_at: string | null;
    cycle_name: string;
    user_id: number;
    reference_no: string | null;
    full_name: string | null;
    issue: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    items: {
        data: Item[];
        links: PaginationLink[];
        total: number;
    };
};

export default function ConfirmedMatchReconciliation({ items }: Props) {
    return (
        <>
            <Head title="Confirmed Match Reconciliation" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <Link
                        href="/mutual-match-runs"
                        className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                    >
                        ← Back to saved matching runs
                    </Link>

                    <h1 className="mt-3 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Confirmed Match Reconciliation
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Confirmed pairs with an eligibility issue or an application status,
                            cycle or service change.
                    </p>
                </div>

                <div className="rounded-md bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    Review these records manually. The existing match decision
                    has not been changed or cancelled.
                </div>

                <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 className="font-semibold text-gray-900 dark:text-gray-100">
                            Records requiring review
                        </h2>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            A pair may appear twice if both participants have an issue.
                            Total affected participant records: {items.total}
                        </p>
                    </div>

                    {items.data.length === 0 ? (
                        <p className="p-5 text-sm text-gray-600 dark:text-gray-400">
                            No issues covered by these checks were found in confirmed matches.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    <tr>
                                        <th className="px-4 py-3">Cycle</th>
                                        <th className="px-4 py-3">Employee</th>
                                        <th className="px-4 py-3">Reference</th>
                                        <th className="px-4 py-3">Issue</th>
                                        <th className="px-4 py-3">Decision date</th>
                                        <th className="px-4 py-3">Run</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200 text-gray-900 dark:divide-gray-700 dark:text-gray-100">
                                    {items.data.map((item) => (
                                        <tr key={`${item.decision_id}-${item.user_id}`}>
                                            <td className="px-4 py-3">
                                                {item.cycle_name}
                                            </td>

                                            <td className="px-4 py-3">
                                                {item.full_name ?? '—'}
                                            </td>

                                            <td className="px-4 py-3">
                                                {item.reference_no ?? '—'}
                                            </td>

                                            <td className="px-4 py-3">
                                                {item.issue}
                                            </td>

                                            <td className="px-4 py-3">
                                                {item.decided_at ?? '—'}
                                            </td>

                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/mutual-match-runs/${item.run_id}`}
                                                    className="font-medium text-blue-700 hover:underline dark:text-blue-400"
                                                >
                                                    View run #{item.run_id}
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {items.links.length > 3 && (
                        <div className="flex flex-wrap gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                            {items.links.map((link, index) => {
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