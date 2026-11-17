import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Bell,
    BriefcaseBusiness,
    Building2,
    ClipboardCheck,
    FilePenLine,
    FileText,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { dashboard } from '@/routes';

type DashboardCard = {
    key: string;
    title: string;
    value: number;
    description: string;
    href: string;
    icon: string;
};

type LatestApplication = {
    reference_no: string | null;
    cycle: string | null;
    status: string;
    submitted_at: string | null;
};

type QuickLink = {
    title: string;
    description: string;
    href: string;
};

type Props = {
    dashboardData: {
        name: string;
        roles: string[];
        cards: DashboardCard[];
        latestApplication: LatestApplication | null;
        quickLinks: QuickLink[];
    };
};

const icons: Record<string, LucideIcon> = {
    bell: Bell,
    file: FileText,
    edit: FilePenLine,
    office: Building2,
    review: ClipboardCheck,
    users: Users,
    service: BriefcaseBusiness,
};

function formatStatus(status: string): string {
    const labels: Record<string, string> = {
        draft: 'Draft',
        submitted: 'Awaiting office review',
        office_recommended: 'Awaiting district review',
        approved: 'Approved',
        rejected: 'Rejected',
    };

    return labels[status] ?? status.replaceAll('_', ' ');
}

export default function Dashboard({ dashboardData }: Props) {
    const { name, roles, cards, latestApplication, quickLinks } =
        dashboardData;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                        Welcome, {name}
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        District Secretariat Transfer System
                    </p>

                    {roles.length > 0 && (
                        <div className="mt-3 flex flex-wrap gap-2">
                            {roles.map((role) => (
                                <span
                                    key={role}
                                    className="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                                >
                                    {role}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {cards.map((card) => {
                        const Icon = icons[card.icon] ?? FileText;

                        return (
                            <Link
                                key={card.key}
                                href={card.href}
                                className="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-700"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            {card.title}
                                        </p>

                                        <p className="mt-3 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                                            {card.value}
                                        </p>
                                    </div>

                                    <div className="rounded-lg bg-blue-50 p-2.5 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                        <Icon className="size-5" />
                                    </div>
                                </div>

                                <div className="mt-4 flex items-center justify-between gap-3">
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {card.description}
                                    </p>

                                    <ArrowRight className="size-4 shrink-0 text-gray-400 transition group-hover:translate-x-1 group-hover:text-blue-700" />
                                </div>
                            </Link>
                        );
                    })}
                </div>

                {latestApplication && (
                    <section className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Latest application
                                </h2>

                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Your most recently created transfer
                                    application.
                                </p>
                            </div>

                            <Link
                                href="/transfer-applications"
                                className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                            >
                                View my applications
                            </Link>
                        </div>

                        <dl className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Reference
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {latestApplication.reference_no ?? '—'}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Transfer cycle
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {latestApplication.cycle ?? '—'}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Status
                                </dt>
                                <dd className="mt-1 text-sm font-medium text-blue-700 dark:text-blue-400">
                                    {formatStatus(latestApplication.status)}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Submitted
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {latestApplication.submitted_at ?? '—'}
                                </dd>
                            </div>
                        </dl>
                    </section>
                )}

                {quickLinks.length > 0 && (
                    <section className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Quick links
                        </h2>

                        <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {quickLinks.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="rounded-lg border border-gray-200 p-4 transition hover:border-blue-300 hover:bg-blue-50 dark:border-gray-700 dark:hover:border-blue-700 dark:hover:bg-gray-800"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <h3 className="font-medium text-gray-900 dark:text-gray-100">
                                            {link.title}
                                        </h3>

                                        <ArrowRight className="size-4 shrink-0 text-gray-400" />
                                    </div>

                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {link.description}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};