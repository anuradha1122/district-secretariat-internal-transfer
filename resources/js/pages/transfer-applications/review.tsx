import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Office = {
    id: number;
    name: string;
    code: string;
};

type Application = {
    id: number;
    reference_no: string;
    status: string;

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

    cycle: { name: string };
    current_office: Office | null;
    service: { name: string; code: string } | null;

    children: {
        id: number;
        name: string;
        age: number | null;
        school: string | null;
    }[];

    work_history: {
        id: number;
        workplace_name: string;
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

const sectionClass =
    'rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6';

function display(value: string | number | null | undefined): string {
    return value === null || value === undefined || value === ''
        ? 'Not entered'
        : String(value);
}

function yesNo(value: boolean | null): string {
    return value === null ? 'Not selected' : value ? 'Yes' : 'No';
}

function dateOnly(value: string | null): string {
    return value ? value.slice(0, 10) : 'Not entered';
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
            <dt className="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                {label}
            </dt>

            <dd className="mt-1 whitespace-pre-wrap text-sm text-slate-900 dark:text-zinc-100">
                {display(value)}
            </dd>
        </div>
    );
}

export default function ReviewTransferApplication({
    application,
}: Props) {
    const form = useForm({
        declaration_accepted: false,
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        if (
            !window.confirm(
                'Submit this application? You will not be able to edit it after submission.',
            )
        ) {
            return;
        }

        form.post(
            `/transfer-applications/${application.id}/submit`,
        );
    };

    return (
        <>
            <Head title="Review Transfer Application" />

            <div className="min-h-full bg-slate-50 p-4 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100 sm:p-6 lg:p-8">
                <div className="mx-auto max-w-5xl space-y-6">
                    <div>
                        <Link
                            href="/transfer-applications"
                            className="text-sm text-blue-700 hover:underline dark:text-blue-400"
                        >
                            ← My applications
                        </Link>

                        <h1 className="mt-3 text-2xl font-semibold">
                            Review your application
                        </h1>

                        <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                            {application.cycle.name} ·{' '}
                            {application.reference_no}
                        </p>

                        <p className="mt-1 text-sm capitalize">
                            Status: {application.status}
                        </p>
                    </div>

                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold">
                            Personal information
                        </h2>

                        <dl className="grid gap-5 md:grid-cols-2">
                            <Detail label="Full name" value={application.full_name} />
                            <Detail
                                label="Name with initials in Sinhala"
                                value={application.name_with_initials_sinhala}
                            />
                            <Detail
                                label="Name with initials in English"
                                value={application.name_with_initials_english}
                            />
                            <Detail
                                label="Date of birth"
                                value={dateOnly(application.date_of_birth)}
                            />
                            <Detail label="Sex" value={application.gender} />
                            <Detail
                                label="Permanent address"
                                value={application.permanent_address}
                            />
                            <Detail
                                label="Temporary address"
                                value={application.temporary_address}
                            />
                            <Detail
                                label="Office telephone"
                                value={application.office_phone}
                            />
                            <Detail
                                label="Personal telephone"
                                value={application.personal_phone}
                            />
                            <Detail
                                label="Marital status"
                                value={application.marital_status}
                            />
                            <Detail
                                label="Spouse’s name"
                                value={application.spouse_name}
                            />
                            <Detail
                                label="Spouse’s occupation"
                                value={application.spouse_occupation}
                            />
                            <Detail
                                label="Spouse’s workplace"
                                value={application.spouse_workplace}
                            />
                        </dl>
                    </section>

                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold">
                            Children
                        </h2>

                        {application.children.length === 0 ? (
                            <p className="text-sm text-slate-600 dark:text-zinc-400">
                                No children added.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {application.children.map((child) => (
                                    <div
                                        key={child.id}
                                        className="rounded-lg border border-slate-200 p-4 text-sm dark:border-zinc-700"
                                    >
                                        <p className="font-medium">
                                            {child.name}
                                        </p>
                                        <p>Age: {display(child.age)}</p>
                                        <p>School: {display(child.school)}</p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold">
                            Service information
                        </h2>

                        <dl className="grid gap-5 md:grid-cols-2">
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
                                label="Current office"
                                value={application.current_office?.name}
                            />
                            <Detail
                                label="Service"
                                value={application.service?.name}
                            />
                            <Detail
                                label="Date reported to current office"
                                value={dateOnly(
                                    application.reported_to_current_office_on,
                                )}
                            />
                        </dl>
                    </section>

                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold">
                            Previous workplaces
                        </h2>

                        {application.work_history.length === 0 ? (
                            <p className="text-sm text-slate-600 dark:text-zinc-400">
                                No previous workplaces added.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {application.work_history.map((history) => (
                                    <div
                                        key={history.id}
                                        className="rounded-lg border border-slate-200 p-4 text-sm dark:border-zinc-700"
                                    >
                                        <p className="font-medium">
                                            {history.workplace_name}
                                        </p>
                                        <p>
                                            {dateOnly(history.from_date)} to{' '}
                                            {dateOnly(history.to_date)}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className={sectionClass}>
                        <h2 className="mb-5 text-lg font-semibold">
                            Preferred offices
                        </h2>

                        {application.preferences.length === 0 ? (
                            <p className="text-sm text-slate-600 dark:text-zinc-400">
                                No preferred offices selected.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {application.preferences.map((preference) => (
                                    <div
                                        key={preference.id}
                                        className="rounded-lg border border-slate-200 p-4 text-sm dark:border-zinc-700"
                                    >
                                        <p className="font-medium">
                                            {preference.priority}.{' '}
                                            {preference.office?.name ??
                                                'Office unavailable'}
                                        </p>

                                        <p className="mt-1">
                                            Reason: {display(preference.reason)}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}

                        <dl className="mt-5 grid gap-5">
                            <Detail
                                label="Accept a nearby office"
                                value={yesNo(application.accept_nearby_office)}
                            />
                            <Detail
                                label="General reason"
                                value={application.transfer_reason}
                            />
                        </dl>
                    </section>

                    {application.status === 'draft' ? (
                        <section className={sectionClass}>
                            <h2 className="text-lg font-semibold">
                                Declaration and submission
                            </h2>

                            <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                Check all details before submitting. After
                                submission, you can download the PDF for
                                printing and signing.
                            </p>

                            {form.errors.application && (
                                <p
                                    role="alert"
                                    className="mt-4 text-sm text-red-600 dark:text-red-400"
                                >
                                    {form.errors.application}
                                </p>
                            )}

                            <form onSubmit={submit} className="mt-5 space-y-4">
                                <div className="flex items-start gap-3">
                                    <input
                                        id="declaration_accepted"
                                        type="checkbox"
                                        checked={
                                            form.data.declaration_accepted
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'declaration_accepted',
                                                event.target.checked,
                                            )
                                        }
                                        className="mt-1"
                                    />

                                    <label
                                        htmlFor="declaration_accepted"
                                        className="text-sm leading-6"
                                    >
                                        I declare that the information I have
                                        provided is true and accurate.
                                    </label>
                                </div>

                                {form.errors.declaration_accepted && (
                                    <p className="text-sm text-red-600 dark:text-red-400">
                                        {form.errors.declaration_accepted}
                                    </p>
                                )}

                                <div className="flex flex-wrap gap-3">
                                    <Link
                                        href={`/transfer-applications/${application.id}/edit`}
                                        className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-700"
                                    >
                                        Edit draft
                                    </Link>

                                    <button
                                        type="submit"
                                        disabled={
                                            form.processing ||
                                            !form.data.declaration_accepted
                                        }
                                        className="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60"
                                    >
                                        {form.processing
                                            ? 'Submitting...'
                                            : 'Submit application'}
                                    </button>
                                </div>
                            </form>
                        </section>
                    ) : (
                        <section className={sectionClass}>
                            <h2 className="text-lg font-semibold">
                                Application submitted
                            </h2>

                            <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                Download, print and sign the application, then
                                give it to your office head.
                            </p>

                            <a
                                href={`/transfer-applications/${application.id}/pdf`}
                                className="mt-4 inline-block rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800"
                            >
                                Download application PDF
                            </a>
                        </section>
                    )}
                </div>
            </div>
        </>
    );
}
