import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    ArrowRightLeft,
    BadgeCheck,
    Building2,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    LockKeyhole,
    UsersRound,
} from 'lucide-react';

type HomePageProps = {
    auth: {
        user: {
            name: string;
        } | null;
    };
};

const processSteps = [
    {
        number: '01',
        title: 'Prepare your application',
        description:
            'Sign in, complete your transfer application, and select your preferred offices.',
        icon: FileText,
    },
    {
        number: '02',
        title: 'Office review',
        description:
            'Your office head reviews your application and records a recommendation.',
        icon: Building2,
    },
    {
        number: '03',
        title: 'District review',
        description:
            'The district office reviews the recommendation and records its decision.',
        icon: ClipboardCheck,
    },
    {
        number: '04',
        title: 'Mutual transfer matching',
        description:
            'Authorized officers can review potential mutual transfer matches.',
        icon: ArrowRightLeft,
    },
];

const features = [
    {
        title: 'One place to apply',
        description:
            'Complete your application and follow its progress through the review process.',
        icon: FileText,
    },
    {
        title: 'Clear review process',
        description:
            'Office and district reviews are recorded as the application progresses.',
        icon: BadgeCheck,
    },
    {
        title: 'Updates in your account',
        description:
            'Sign in to view application notifications and the latest status.',
        icon: UsersRound,
    },
];

export default function Welcome() {
    const { auth } = usePage<HomePageProps>().props;
    const isSignedIn = Boolean(auth?.user);

    return (
        <>
            <Head title="District Secretariat Transfer System">
                <meta
                    name="description"
                    content="Apply for transfers and follow your application through the District Secretariat Transfer System."
                />
            </Head>

            <div className="min-h-screen bg-slate-50 text-slate-900">
                <header className="border-b border-slate-200 bg-white">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-800 text-white">
                                <Building2 className="size-6" />
                            </span>

                            <span>
                                <span className="block text-sm font-semibold leading-tight text-slate-900 sm:text-base">
                                    District Secretariat
                                </span>

                                <span className="block text-xs text-slate-500 sm:text-sm">
                                    Transfer System
                                </span>
                            </span>
                        </Link>

                        <Link
                            href={isSignedIn ? '/dashboard' : '/login'}
                            className="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-900"
                        >
                            {isSignedIn ? 'Dashboard' : 'Sign in'}

                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                </header>

                <main>
                    <section className="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-900 text-white">
                        <div
                            aria-hidden="true"
                            className="absolute -right-20 -top-24 size-80 rounded-full bg-blue-400/10 blur-3xl"
                        />

                        <div className="relative mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:px-8 lg:py-24">
                            <div>
                                <span className="inline-flex items-center gap-2 rounded-full border border-blue-300/25 bg-white/10 px-3 py-1.5 text-xs font-medium tracking-wide text-blue-100">
                                    <span className="size-2 rounded-full bg-emerald-400" />
                                    Employee transfer services
                                </span>

                                <h1 className="mt-6 max-w-2xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">
                                    Your transfer application,{' '}
                                    <span className="text-blue-200">
                                        made easier to follow.
                                    </span>
                                </h1>

                                <p className="mt-5 max-w-xl text-base leading-7 text-blue-100 sm:text-lg">
                                    Apply for a transfer, follow your
                                    application through office and district
                                    review, and receive updates in one place.
                                </p>

                                <div className="mt-8 flex flex-wrap gap-3">
                                    <Link
                                        href={
                                            isSignedIn
                                                ? '/dashboard'
                                                : '/login'
                                        }
                                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-blue-900 shadow-sm transition hover:bg-blue-50"
                                    >
                                        {isSignedIn
                                            ? 'Go to dashboard'
                                            : 'Sign in to your account'}

                                        <ArrowRight className="size-4" />
                                    </Link>

                                    <a
                                        href="#how-it-works"
                                        className="inline-flex items-center justify-center rounded-lg border border-white/30 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                                    >
                                        How it works
                                    </a>
                                </div>

                                <div className="mt-8 flex items-start gap-2 text-sm text-blue-100">
                                    <LockKeyhole className="mt-0.5 size-4 shrink-0" />

                                    <p>
                                        Access is provided to authorized users.
                                        Employees can contact their
                                        administrator for account activation.
                                    </p>
                                </div>
                            </div>

                            <div className="rounded-2xl border border-white/15 bg-white/10 p-5 shadow-xl backdrop-blur sm:p-7">
                                <div className="flex items-center justify-between gap-4 border-b border-white/15 pb-5">
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-widest text-blue-200">
                                            Transfer process
                                        </p>

                                        <h2 className="mt-2 text-xl font-semibold">
                                            From application to review
                                        </h2>
                                    </div>

                                    <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/10">
                                        <ClipboardCheck className="size-6 text-blue-100" />
                                    </span>
                                </div>

                                <div className="mt-5 space-y-4">
                                    {[
                                        'Complete and submit your application',
                                        'Office head records a recommendation',
                                        'District office records a decision',
                                        'View updates in your account',
                                    ].map((item) => (
                                        <div
                                            key={item}
                                            className="flex items-center gap-3"
                                        >
                                            <CheckCircle2 className="size-5 shrink-0 text-emerald-300" />

                                            <p className="text-sm text-blue-50">
                                                {item}
                                            </p>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-6 rounded-xl border border-blue-200/15 bg-blue-950/30 p-4">
                                    <p className="text-sm font-medium text-white">
                                        Already have an account?
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-blue-200">
                                        Sign in to see the actions and
                                        information available to your role.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:px-8">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold uppercase tracking-wider text-blue-700">
                                Transfer services
                            </p>

                            <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                                A clearer way to manage applications
                            </h2>

                            <p className="mt-3 leading-7 text-slate-600">
                                Employees and authorized officers can follow
                                the transfer process from the same system.
                            </p>
                        </div>

                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            {features.map((feature) => {
                                const Icon = feature.icon;

                                return (
                                    <article
                                        key={feature.title}
                                        className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                                    >
                                        <span className="flex size-11 items-center justify-center rounded-lg bg-blue-50 text-blue-800">
                                            <Icon className="size-5" />
                                        </span>

                                        <h3 className="mt-5 text-lg font-semibold">
                                            {feature.title}
                                        </h3>

                                        <p className="mt-2 text-sm leading-6 text-slate-600">
                                            {feature.description}
                                        </p>
                                    </article>
                                );
                            })}
                        </div>
                    </section>

                    <section
                        id="how-it-works"
                        className="border-y border-slate-200 bg-white"
                    >
                        <div className="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:px-8">
                            <div className="max-w-2xl">
                                <p className="text-sm font-semibold uppercase tracking-wider text-blue-700">
                                    How it works
                                </p>

                                <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                                    The transfer process
                                </h2>

                                <p className="mt-3 leading-7 text-slate-600">
                                    Follow your application from submission
                                    through the review stages.
                                </p>
                            </div>

                            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {processSteps.map((step) => {
                                    const Icon = step.icon;

                                    return (
                                        <article
                                            key={step.number}
                                            className="rounded-xl border border-slate-200 bg-slate-50 p-5"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm font-semibold text-blue-700">
                                                    {step.number}
                                                </span>

                                                <Icon className="size-5 text-blue-800" />
                                            </div>

                                            <h3 className="mt-5 font-semibold text-slate-900">
                                                {step.title}
                                            </h3>

                                            <p className="mt-2 text-sm leading-6 text-slate-600">
                                                {step.description}
                                            </p>
                                        </article>
                                    );
                                })}
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:px-8">
                        <div className="flex flex-col gap-6 rounded-2xl bg-blue-900 p-7 text-white sm:p-9 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold">
                                    Ready to access the system?
                                </h2>

                                <p className="mt-2 max-w-xl text-sm leading-6 text-blue-100">
                                    Sign in to manage your application or
                                    access your assigned review functions.
                                </p>
                            </div>

                            <Link
                                href={isSignedIn ? '/dashboard' : '/login'}
                                className="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-blue-900 transition hover:bg-blue-50"
                            >
                                {isSignedIn
                                    ? 'Go to dashboard'
                                    : 'Sign in'}

                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-slate-200 bg-white">
                    <div className="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                        <p>
                            District Secretariat Transfer System
                        </p>

                        <p>
                            For assistance, contact your system administrator.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}