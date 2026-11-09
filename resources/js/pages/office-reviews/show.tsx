import { Head, Link } from '@inertiajs/react';

type Office = {
    id: number;
    code: string;
    name: string;
};

type Application = {
    id: number;
    reference_no: string | null;
    status: string;
    submitted_at: string | null;

    full_name: string | null;
    name_with_initials_sinhala: string | null;
    name_with_initials_english: string | null;
    date_of_birth: string | null;
    gender: string | null;

    permanent_address: string | null;
    temporary_address: string | null;
    office_phone: string | null;
    personal_phone: string | null;

    marital_status: string | null;
    spouse_name: string | null;
    spouse_occupation: string | null;
    spouse_workplace: string | null;

    post: string | null;
    grade: string | null;
    appointment_letter_no: string | null;
    is_confirmed: boolean | null;
    reported_to_current_office_on: string | null;

    accept_nearby_office: boolean | null;
    transfer_reason: string | null;

    current_office: Office | null;
    service: { id: number; code: string; name: string } | null;
    cycle: { id: number; name: string } | null;

    children: {
        id: number;
        name: string | null;
        age: number | null;
        school: string | null;
    }[];

    work_history: {
        id: number;
        workplace_name: string | null;
        from_date: string | null;
        to_date: string | null;
    }[];

    preferences: {
        id: number;
        priority: number;
        reason: string | null;
        office: Office | null;
    }[];
};

type Props = {
    application: Application;
};

function display(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}

function yesNo(value: boolean | null): string {
    if (value === null || value === undefined) {
        return '—';
    }

    return value ? 'Yes' : 'No';
}

function Detail({
    label,
    value,
}: {
    label: string;
    value: string | number | null | undefined;
}) {
    return (
        <div>
            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                {label}
            </dt>

            <dd className="mt-1 whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100">
                {display(value)}
            </dd>
        </div>
    );
}

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {title}
            </h2>

            <div className="mt-4">{children}</div>
        </section>
    );
}

export default function OfficeReviewShow({ application }: Props) {
    return (
        <>
            <Head title="View Transfer Application" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <Link
                        href="/office-reviews"
                        className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                    >
                        ← Back to Office Review Inbox
                    </Link>

                    <h1 className="mt-3 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Transfer Application
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Reference: {display(application.reference_no)}
                    </p>
                </div>

                <div className="rounded-md bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                    This page is currently read-only. Review decisions and confirmation
                    of the signed paper application will be added in the next step.
                </div>

                <Section title="Application">
                    <dl className="grid gap-4 md:grid-cols-3">
                        <Detail label="Cycle" value={application.cycle?.name} />
                        <Detail label="Status" value={application.status} />
                        <Detail label="Submitted" value={application.submitted_at} />
                    </dl>
                </Section>

                <Section title="Personal information">
                    <dl className="grid gap-4 md:grid-cols-2">
                        <Detail label="Full name" value={application.full_name} />
                        <Detail
                            label="Name with initials — Sinhala"
                            value={application.name_with_initials_sinhala}
                        />
                        <Detail
                            label="Name with initials — English"
                            value={application.name_with_initials_english}
                        />
                        <Detail label="Date of birth" value={application.date_of_birth} />
                        <Detail label="Gender" value={application.gender} />
                        <Detail label="Permanent address" value={application.permanent_address} />
                        <Detail label="Temporary address" value={application.temporary_address} />
                        <Detail label="Office telephone" value={application.office_phone} />
                        <Detail label="Personal telephone" value={application.personal_phone} />
                    </dl>
                </Section>

                <Section title="Family information">
                    <dl className="grid gap-4 md:grid-cols-2">
                        <Detail label="Marital status" value={application.marital_status} />
                        <Detail label="Spouse name" value={application.spouse_name} />
                        <Detail label="Spouse occupation" value={application.spouse_occupation} />
                        <Detail label="Spouse workplace" value={application.spouse_workplace} />
                    </dl>

                    <h3 className="mt-6 font-medium text-gray-900 dark:text-gray-100">
                        Children
                    </h3>

                    {application.children.length === 0 ? (
                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            No children listed.
                        </p>
                    ) : (
                        <div className="mt-3 space-y-3">
                            {application.children.map((child) => (
                                <div
                                    key={child.id}
                                    className="rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700"
                                >
                                    <dl className="grid gap-3 md:grid-cols-3">
                                        <Detail label="Name" value={child.name} />
                                        <Detail label="Age" value={child.age} />
                                        <Detail label="School" value={child.school} />
                                    </dl>
                                </div>
                            ))}
                        </div>
                    )}
                </Section>

                <Section title="Employment information">
                    <dl className="grid gap-4 md:grid-cols-2">
                        <Detail label="Current office" value={application.current_office?.name} />
                        <Detail label="Service" value={application.service?.name} />
                        <Detail label="Post" value={application.post} />
                        <Detail label="Grade" value={application.grade} />
                        <Detail
                            label="Appointment letter number"
                            value={application.appointment_letter_no}
                        />
                        <Detail
                            label="Confirmed in post"
                            value={yesNo(application.is_confirmed)}
                        />
                        <Detail
                            label="Reported to current office"
                            value={application.reported_to_current_office_on}
                        />
                    </dl>
                </Section>

                <Section title="Previous workplaces">
                    {application.work_history.length === 0 ? (
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            No previous workplaces listed.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {application.work_history.map((history) => (
                                <div
                                    key={history.id}
                                    className="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                                >
                                    <dl className="grid gap-3 md:grid-cols-3">
                                        <Detail
                                            label="Workplace"
                                            value={history.workplace_name}
                                        />
                                        <Detail label="From" value={history.from_date} />
                                        <Detail label="To" value={history.to_date} />
                                    </dl>
                                </div>
                            ))}
                        </div>
                    )}
                </Section>

                <Section title="Preferred offices">
                    {application.preferences.length === 0 ? (
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            No preferred offices listed.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {application.preferences.map((preference) => (
                                <div
                                    key={preference.id}
                                    className="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                                >
                                    <dl className="grid gap-3 md:grid-cols-3">
                                        <Detail
                                            label="Priority"
                                            value={preference.priority}
                                        />
                                        <Detail
                                            label="Office"
                                            value={preference.office?.name}
                                        />
                                        <Detail
                                            label="Reason"
                                            value={preference.reason}
                                        />
                                    </dl>
                                </div>
                            ))}
                        </div>
                    )}

                    <dl className="mt-5 grid gap-4 md:grid-cols-2">
                        <Detail
                            label="Willing to accept a nearby office"
                            value={yesNo(application.accept_nearby_office)}
                        />
                        <Detail
                            label="General transfer reason"
                            value={application.transfer_reason}
                        />
                    </dl>
                </Section>
            </div>
        </>
    );
}
