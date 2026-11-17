import { Head, Link, router } from '@inertiajs/react';

type NotificationItem = {
    id: string;
    title: string;
    message: string;
    reference_no: string | null;
    read_at: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    notifications: {
        data: NotificationItem[];
        links: PaginationLink[];
        total: number;
    };
    unreadCount: number;
};

export default function NotificationsIndex({
    notifications,
    unreadCount,
}: Props) {
    function markAsRead(id: string) {
        router.post(
            `/notifications/${encodeURIComponent(id)}/read`,
            {},
            { preserveScroll: true },
        );
    }

    function markAllAsRead() {
        router.post(
            '/notifications/read-all',
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Notifications" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            Notifications
                        </h1>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Application updates: {unreadCount} unread.
                        </p>
                    </div>

                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={markAllAsRead}
                            className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Mark all as read
                        </button>
                    )}
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    {notifications.data.length === 0 ? (
                        <p className="p-6 text-sm text-gray-600 dark:text-gray-400">
                            You do not have any notifications yet.
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                            {notifications.data.map((notification) => (
                                <li
                                    key={notification.id}
                                    className={`p-5 ${
                                        notification.read_at === null
                                            ? 'bg-blue-50 dark:bg-blue-950/30'
                                            : ''
                                    }`}
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {notification.title}
                                                </h2>

                                                {notification.read_at === null && (
                                                    <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        Unread
                                                    </span>
                                                )}
                                            </div>

                                            <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                                {notification.message}
                                            </p>

                                            {notification.reference_no && (
                                                <p className="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                                    Reference:{' '}
                                                    {notification.reference_no}
                                                </p>
                                            )}

                                            {notification.created_at && (
                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {notification.created_at}
                                                </p>
                                            )}
                                        </div>

                                        {notification.read_at === null && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    markAsRead(notification.id)
                                                }
                                                className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                                            >
                                                Mark as read
                                            </button>
                                        )}
                                    </div>

                                    <Link
                                        href="/transfer-applications"
                                        className="mt-3 inline-block text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                                    >
                                        View my applications
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}

                    {notifications.links.length > 3 && (
                        <div className="flex flex-wrap gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                            {notifications.links.map((link, index) => {
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