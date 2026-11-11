import { Head, Link, useForm } from '@inertiajs/react';

type Office = {
    id: number;
    code: string;
    name: string;
};

type Application = {
    id: number;
    reference_no: string | null;
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

type OfficeDecision = {
    id: number;
    recommendation: string | null;
    insufficient_service: boolean;
    replacement_required: boolean;
    pending_matters: boolean;
    other_circumstances: boolean;
    signed_paper_verified: boolean;
    remarks: string | null;
    decided_at: string | null;
    reviewer: { id: number; name: string } | null;
};

type Props = {
    application: Application;
    officeDecision: OfficeDecision;
    canDecide: boolean;
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

export default function DistrictReviewShow({
    application,
    officeDecision,
    canDecide,
}: Props) {
    const form = useForm({
        office_review_decision_id: officeDecision.id,
        outcome: '',
        remarks: '',
    });

    function submit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!window.confirm('Record this district decision?')) {
            return;
        }

        form.post(`/district-reviews/${application.id}/decision`, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="District Review" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <Link
                        href="/district-reviews"
                        className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                    >
                        ← Back to District Review Inbox
                    </Link>

                    <h1 className="mt-3 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        District Review
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Reference: {display(application.reference_no)}
                    </p>
                </div>

                <Section title="Office Head recommendation">
                    <dl className="grid gap-4 md:grid-cols-2">
                        <Detail
                            label="Recommendation"
                            value={
                                officeDecision.recommendation === 'recommended'
                                    ? 'Recommended'
                                    : 'Not recommended'
                            }
                        />

                        <Detail
                            label="Reviewed by"
                            value={officeDecision.reviewer?.name}
                        />

                        <Detail
                            label="Decision date"
                            value={officeDecision.decided_at}
                        />

                        <Detail
                            label="Signed paper checked"
                            value={yesNo(officeDecision.signed_paper_verified)}
                        />

                        <Detail
                            label="Insufficient service at current office"
                            value={yesNo(officeDecision.insufficient_service)}
                        />

                        <Detail
                            label="Replacement required"
                            value={yesNo(officeDecision.replacement_required)}
                        />

                        <Detail
                            label="Pending matters"
                            value={yesNo(officeDecision.pending_matters)}
                        />

                        <Detail
                            label="Other circumstances"
                            value={yesNo(officeDecision.other_circumstances)}
                        />

                        <Detail
                            label="Office Head remarks"
                            value={officeDecision.remarks}
                        />
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
                                    className="rounded-md border border-gray-200 p-3 dark:border-gray-700"
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
                        <Detail label="Cycle" value={application.cycle?.name} />
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
                                        <Detail label="Workplace" value={history.workplace_name} />
                                        <Detail label="From" value={history.from_date} />
                                        <Detail label="To" value={history.to_date} />
                                    </dl>
                                </div>
                            ))}
                        </div>
                    )}
                </Section>

                <Section title="Preferred offices">
                    <div className="space-y-3">
                        {application.preferences.map((preference) => (
                            <div
                                key={preference.id}
                                className="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                            >
                                <dl className="grid gap-3 md:grid-cols-3">
                                    <Detail label="Priority" value={preference.priority} />
                                    <Detail label="Office" value={preference.office?.name} />
                                    <Detail label="Reason" value={preference.reason} />
                                </dl>
                            </div>
                        ))}
                    </div>

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

                {canDecide && (
                    <Section title="District decision">
                        <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Approval allows this application to be considered for a transfer.
                            It does not issue a transfer order.
                        </p>

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label
                                    htmlFor="outcome"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Decision <span className="text-red-600">*</span>
                                </label>

                                <select
                                    id="outcome"
                                    value={form.data.outcome}
                                    onChange={(event) =>
                                        form.setData('outcome', event.target.value)
                                    }
                                    required
                                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                >
                                    <option value="">Select a decision</option>
                                    <option value="approved">Approve application</option>
                                    <option value="rejected">Reject application</option>
                                </select>

                                {form.errors.outcome && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.outcome}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="remarks"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Remarks
                                    {form.data.outcome === 'rejected' && (
                                        <span className="text-red-600"> *</span>
                                    )}
                                </label>

                                <textarea
                                    id="remarks"
                                    rows={4}
                                    maxLength={5000}
                                    value={form.data.remarks}
                                    onChange={(event) =>
                                        form.setData('remarks', event.target.value)
                                    }
                                    required={form.data.outcome === 'rejected'}
                                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                />

                                {form.errors.remarks && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.remarks}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
                            >
                                {form.processing ? 'Saving...' : 'Record District Decision'}
                            </button>
                        </form>
                    </Section>
                )}
            </div>
        </>
    );
}
