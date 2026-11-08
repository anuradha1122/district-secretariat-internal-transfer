import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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

type Child = {
    name: string;
    age: string;
    school: string;
};

type WorkHistory = {
    workplace_name: string;
    from_date: string;
    to_date: string;
};

type Preference = {
    office_id: string;
    reason: string;
};

type Application = {
    id: number;
    reference_no: string;
    cycle: { id: number; name: string };

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
    current_office_id: number | null;
    service_id: number | null;
    reported_to_current_office_on: string | null;
    accept_nearby_office: boolean | null;
    transfer_reason: string | null;

    children: {
        name: string;
        age: number | null;
        school: string | null;
    }[];

    work_history: {
        workplace_name: string;
        from_date: string | null;
        to_date: string | null;
    }[];

    preferences: {
        office_id: number;
        reason: string | null;
    }[];
};

type Props = {
    application: Application;
    offices: Office[];
    services: Service[];
};

const inputClass =
    'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100';

const labelClass =
    'block text-sm font-medium text-slate-700 dark:text-zinc-200';

// Fields marked here are required when the application is submitted.
const requiredForSubmission = new Set([
    'full_name', 'name_with_initials_english', 'date_of_birth',
    'permanent_address', 'personal_phone', 'post', 'grade',
    'appointment_letter_no', 'reported_to_current_office_on',
]);

function RequiredStar() {
    return <span className="ml-1 font-bold text-red-600 dark:text-red-400" aria-label="required for submission">*</span>;
}

const sectionClass =
    'rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6';

export default function TransferApplicationForm({
    application,
    offices,
    services,
}: Props) {
    const form = useForm({
        full_name: application.full_name ?? '',
        name_with_initials_sinhala:
            application.name_with_initials_sinhala ?? '',
        name_with_initials_english:
            application.name_with_initials_english ?? '',
        date_of_birth: application.date_of_birth ?? '',
        gender: application.gender ?? '',
        permanent_address: application.permanent_address ?? '',
        temporary_address: application.temporary_address ?? '',
        office_phone: application.office_phone ?? '',
        personal_phone: application.personal_phone ?? '',
        marital_status: application.marital_status ?? '',
        spouse_name: application.spouse_name ?? '',
        spouse_occupation: application.spouse_occupation ?? '',
        spouse_workplace: application.spouse_workplace ?? '',
        post: application.post ?? '',
        grade: application.grade ?? '',
        appointment_letter_no: application.appointment_letter_no ?? '',
        is_confirmed:
            application.is_confirmed === null
                ? ''
                : application.is_confirmed
                  ? 'yes'
                  : 'no',
        current_office_id: application.current_office_id
            ? String(application.current_office_id)
            : '',
        service_id: application.service_id
            ? String(application.service_id)
            : '',
        reported_to_current_office_on:
            application.reported_to_current_office_on ?? '',
        accept_nearby_office:
            application.accept_nearby_office === null
                ? ''
                : application.accept_nearby_office
                  ? 'yes'
                  : 'no',
        transfer_reason: application.transfer_reason ?? '',

        children: application.children.map((child) => ({
            name: child.name,
            age: child.age === null ? '' : String(child.age),
            school: child.school ?? '',
        })) as Child[],

        work_history: application.work_history.map((history) => ({
            workplace_name: history.workplace_name,
            from_date: history.from_date ?? '',
            to_date: history.to_date ?? '',
        })) as WorkHistory[],

        preferences: application.preferences.map((preference) => ({
            office_id: String(preference.office_id),
            reason: preference.reason ?? '',
        })) as Preference[],
    });

    const errors = form.errors as Record<string, string | undefined>;

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            is_confirmed:
                data.is_confirmed === ''
                    ? null
                    : data.is_confirmed === 'yes',
            accept_nearby_office:
                data.accept_nearby_office === ''
                    ? null
                    : data.accept_nearby_office === 'yes',
        }));

        form.put(`/transfer-applications/${application.id}`, {
            preserveScroll: true,
        });
    };

    const field = (
        label: string,
        name: keyof typeof form.data,
        type = 'text',
    ) => (
        <div>
            <label htmlFor={name} className={labelClass}>
                {label}
                {requiredForSubmission.has(name) && <RequiredStar />}
            </label>

            <input
                id={name}
                type={type}
                value={String(form.data[name] ?? '')}
                onChange={(event) =>
                    form.setData(name as never, event.target.value as never)
                }
                className={inputClass}
            />

            {errors[name] && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                    {errors[name]}
                </p>
            )}
        </div>
    );

    return (
        <>
            <Head title="Transfer Application" />

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
                            Transfer Application
                        </h1>

                        <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                            {application.cycle.name} · {application.reference_no}
                        </p>

                        <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                            Fields marked <span className="font-bold text-red-600 dark:text-red-400">*</span> are required for submission. You can still save an incomplete draft and return later.
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <section className={sectionClass}>
                            <h2 className="mb-5 text-lg font-semibold">
                                1. Personal information
                            </h2>

                            <div className="grid gap-4 md:grid-cols-2">
                                {field('Full name', 'full_name')}
                                {field(
                                    'Name with initials in Sinhala',
                                    'name_with_initials_sinhala',
                                )}
                                {field(
                                    'Name with initials in English',
                                    'name_with_initials_english',
                                )}
                                {field(
                                    'Date of birth',
                                    'date_of_birth',
                                    'date',
                                )}

                                <div>
                                    <label
                                        htmlFor="gender"
                                        className={labelClass}
                                    >
                                        Sex
                                    <RequiredStar />
                                    </label>

                                    <select
                                        id="gender"
                                        value={form.data.gender}
                                        onChange={(event) =>
                                            form.setData(
                                                'gender',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Select</option>
                                        <option value="female">Female</option>
                                        <option value="male">Male</option>
                                    </select>
                                </div>

                                {field(
                                    'Permanent address',
                                    'permanent_address',
                                )}
                                {field(
                                    'Temporary address',
                                    'temporary_address',
                                )}
                                {field('Office telephone', 'office_phone', 'tel')}
                                {field(
                                    'Personal telephone',
                                    'personal_phone',
                                    'tel',
                                )}

                                <div>
                                    <label
                                        htmlFor="marital_status"
                                        className={labelClass}
                                    >
                                        Marital status
                                    <RequiredStar />
                                    </label>

                                    <select
                                        id="marital_status"
                                        value={form.data.marital_status}
                                        onChange={(event) =>
                                            form.setData(
                                                'marital_status',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Select</option>
                                        <option value="married">Married</option>
                                        <option value="unmarried">
                                            Unmarried
                                        </option>
                                    </select>
                                </div>

                                {field('Spouse’s name', 'spouse_name')}
                                {field(
                                    'Spouse’s occupation',
                                    'spouse_occupation',
                                )}
                                {field(
                                    'Spouse’s workplace',
                                    'spouse_workplace',
                                )}
                            </div>
                        </section>

                        <section className={sectionClass}>
                            <div className="mb-5 flex items-center justify-between">
                                <h2 className="text-lg font-semibold">
                                    2. Children
                                </h2>

                                <button
                                    type="button"
                                    onClick={() =>
                                        form.setData('children', [
                                            ...form.data.children,
                                            { name: '', age: '', school: '' },
                                        ])
                                    }
                                    className="text-sm font-medium text-blue-700 dark:text-blue-400"
                                >
                                    + Add child
                                </button>
                            </div>

                            {form.data.children.length === 0 && (
                                <p className="text-sm text-slate-500 dark:text-zinc-400">
                                    No children added.
                                </p>
                            )}

                            <div className="space-y-4">
                                {form.data.children.map((child, index) => (
                                    <div
                                        key={index}
                                        className="rounded-lg border border-slate-200 p-4 dark:border-zinc-700"
                                    >
                                        <div className="grid gap-4 md:grid-cols-3">
                                            {(
                                                [
                                                    ['name', 'Name'],
                                                    ['age', 'Age'],
                                                    ['school', 'School'],
                                                ] as const
                                            ).map(([key, label]) => (
                                                <div key={key}>
                                                    <label
                                                        htmlFor={`child-${index}-${key}`}
                                                        className={labelClass}
                                                    >
                                                        {label}{key === 'name' && <RequiredStar />}
                                                    </label>

                                                    <input
                                                        id={`child-${index}-${key}`}
                                                        type={
                                                            key === 'age'
                                                                ? 'number'
                                                                : 'text'
                                                        }
                                                        value={child[key]}
                                                        onChange={(event) => {
                                                            const items = [
                                                                ...form.data
                                                                    .children,
                                                            ];

                                                            items[index] = {
                                                                ...items[index],
                                                                [key]:
                                                                    event.target
                                                                        .value,
                                                            };

                                                            form.setData(
                                                                'children',
                                                                items,
                                                            );
                                                        }}
                                                        className={inputClass}
                                                    />

                                                    {errors[
                                                        `children.${index}.${key}`
                                                    ] && (
                                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                            {
                                                                errors[
                                                                    `children.${index}.${key}`
                                                                ]
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                form.setData(
                                                    'children',
                                                    form.data.children.filter(
                                                        (_, itemIndex) =>
                                                            itemIndex !== index,
                                                    ),
                                                )
                                            }
                                            className="mt-3 text-sm text-red-700 dark:text-red-400"
                                        >
                                            Remove child
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className={sectionClass}>
                            <h2 className="mb-5 text-lg font-semibold">
                                3. Service information
                            </h2>

                            <div className="grid gap-4 md:grid-cols-2">
                                {field('Post', 'post')}
                                {field('Grade', 'grade')}
                                {field(
                                    'Appointment letter number',
                                    'appointment_letter_no',
                                )}

                                <div>
                                    <label
                                        htmlFor="is_confirmed"
                                        className={labelClass}
                                    >
                                        Confirmed in post?
                                    <RequiredStar />
                                    </label>

                                    <select
                                        id="is_confirmed"
                                        value={form.data.is_confirmed}
                                        onChange={(event) =>
                                            form.setData(
                                                'is_confirmed',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Select</option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        htmlFor="current_office_id"
                                        className={labelClass}
                                    >
                                        Current office
                                    <RequiredStar />
                                    </label>

                                    <select
                                        id="current_office_id"
                                        value={form.data.current_office_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'current_office_id',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Select office</option>

                                        {offices.map((office) => (
                                            <option
                                                key={office.id}
                                                value={office.id}
                                            >
                                                {office.name} ({office.code})
                                            </option>
                                        ))}
                                    </select>

                                    {errors.current_office_id && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {errors.current_office_id}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="service_id"
                                        className={labelClass}
                                    >
                                        Service
                                    <RequiredStar />
                                    </label>

                                    <select
                                        id="service_id"
                                        value={form.data.service_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'service_id',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Select service</option>

                                        {services.map((service) => (
                                            <option
                                                key={service.id}
                                                value={service.id}
                                            >
                                                {service.name} ({service.code})
                                            </option>
                                        ))}
                                    </select>

                                    {errors.service_id && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {errors.service_id}
                                        </p>
                                    )}
                                </div>

                                {field(
                                    'Date reported to current office',
                                    'reported_to_current_office_on',
                                    'date',
                                )}
                            </div>
                        </section>

                        <section className={sectionClass}>
                            <div className="mb-5 flex items-center justify-between">
                                <h2 className="text-lg font-semibold">
                                    4. Previous workplaces
                                </h2>

                                <button
                                    type="button"
                                    onClick={() =>
                                        form.setData('work_history', [
                                            ...form.data.work_history,
                                            {
                                                workplace_name: '',
                                                from_date: '',
                                                to_date: '',
                                            },
                                        ])
                                    }
                                    className="text-sm font-medium text-blue-700 dark:text-blue-400"
                                >
                                    + Add workplace
                                </button>
                            </div>

                            {form.data.work_history.length === 0 && (
                                <p className="text-sm text-slate-500 dark:text-zinc-400">
                                    No previous workplaces added.
                                </p>
                            )}

                            <div className="space-y-4">
                                {form.data.work_history.map((history, index) => (
                                    <div
                                        key={index}
                                        className="rounded-lg border border-slate-200 p-4 dark:border-zinc-700"
                                    >
                                        <div className="grid gap-4 md:grid-cols-3">
                                            {(
                                                [
                                                    [
                                                        'workplace_name',
                                                        'Workplace',
                                                        'text',
                                                    ],
                                                    [
                                                        'from_date',
                                                        'From date',
                                                        'date',
                                                    ],
                                                    [
                                                        'to_date',
                                                        'To date',
                                                        'date',
                                                    ],
                                                ] as const
                                            ).map(([key, label, type]) => (
                                                <div key={key}>
                                                    <label
                                                        htmlFor={`history-${index}-${key}`}
                                                        className={labelClass}
                                                    >
                                                        {label}{key === 'workplace_name' && <RequiredStar />}
                                                    </label>

                                                    <input
                                                        id={`history-${index}-${key}`}
                                                        type={type}
                                                        value={history[key]}
                                                        onChange={(event) => {
                                                            const items = [
                                                                ...form.data
                                                                    .work_history,
                                                            ];

                                                            items[index] = {
                                                                ...items[index],
                                                                [key]:
                                                                    event.target
                                                                        .value,
                                                            };

                                                            form.setData(
                                                                'work_history',
                                                                items,
                                                            );
                                                        }}
                                                        className={inputClass}
                                                    />

                                                    {errors[
                                                        `work_history.${index}.${key}`
                                                    ] && (
                                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                            {
                                                                errors[
                                                                    `work_history.${index}.${key}`
                                                                ]
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                form.setData(
                                                    'work_history',
                                                    form.data.work_history.filter(
                                                        (_, itemIndex) =>
                                                            itemIndex !== index,
                                                    ),
                                                )
                                            }
                                            className="mt-3 text-sm text-red-700 dark:text-red-400"
                                        >
                                            Remove workplace
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className={sectionClass}>
                            <div className="mb-5 flex items-center justify-between">
                                <h2 className="text-lg font-semibold">
                                    5. Preferred offices <RequiredStar />
                                </h2>

                                {form.data.preferences.length < 5 && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            form.setData('preferences', [
                                                ...form.data.preferences,
                                                {
                                                    office_id: '',
                                                    reason: '',
                                                },
                                            ])
                                        }
                                        className="text-sm font-medium text-blue-700 dark:text-blue-400"
                                    >
                                        + Add preference
                                    </button>
                                )}
                            </div>

                            <p className="mb-4 text-sm text-slate-600 dark:text-zinc-400">
                                Add at least one office in your preferred order. You may
                                select up to five.
                            </p>

                            <div className="space-y-4">
                                {form.data.preferences.map(
                                    (preference, index) => (
                                        <div
                                            key={index}
                                            className="rounded-lg border border-slate-200 p-4 dark:border-zinc-700"
                                        >
                                            <p className="mb-3 font-medium">
                                                Preference {index + 1}
                                            </p>

                                            <label
                                                htmlFor={`preference-${index}`}
                                                className={labelClass}
                                            >
                                                Office <RequiredStar />
                                            </label>

                                            <select
                                                id={`preference-${index}`}
                                                value={preference.office_id}
                                                onChange={(event) => {
                                                    const items = [
                                                        ...form.data.preferences,
                                                    ];

                                                    items[index] = {
                                                        ...items[index],
                                                        office_id:
                                                            event.target.value,
                                                    };

                                                    form.setData(
                                                        'preferences',
                                                        items,
                                                    );
                                                }}
                                                className={inputClass}
                                            >
                                                <option value="">
                                                    Select office
                                                </option>

                                                {offices.map((office) => (
                                                    <option
                                                        key={office.id}
                                                        value={office.id}
                                                    >
                                                        {office.name} (
                                                        {office.code})
                                                    </option>
                                                ))}
                                            </select>

                                            {errors[
                                                `preferences.${index}.office_id`
                                            ] && (
                                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                    {
                                                        errors[
                                                            `preferences.${index}.office_id`
                                                        ]
                                                    }
                                                </p>
                                            )}

                                            <label
                                                htmlFor={`reason-${index}`}
                                                className={`${labelClass} mt-4`}
                                            >
                                                Reason for this choice
                                            </label>

                                            <textarea
                                                id={`reason-${index}`}
                                                value={preference.reason}
                                                onChange={(event) => {
                                                    const items = [
                                                        ...form.data.preferences,
                                                    ];

                                                    items[index] = {
                                                        ...items[index],
                                                        reason: event.target.value,
                                                    };

                                                    form.setData(
                                                        'preferences',
                                                        items,
                                                    );
                                                }}
                                                rows={2}
                                                className={inputClass}
                                            />

                                            {errors[
                                                `preferences.${index}.reason`
                                            ] && (
                                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                    {
                                                        errors[
                                                            `preferences.${index}.reason`
                                                        ]
                                                    }
                                                </p>
                                            )}

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    form.setData(
                                                        'preferences',
                                                        form.data.preferences.filter(
                                                            (_, itemIndex) =>
                                                                itemIndex !==
                                                                index,
                                                        ),
                                                    )
                                                }
                                                className="mt-3 text-sm text-red-700 dark:text-red-400"
                                            >
                                                Remove preference
                                            </button>
                                        </div>
                                    ),
                                )}
                            </div>

                            <div className="mt-5">
                                <label
                                    htmlFor="accept_nearby_office"
                                    className={labelClass}
                                >
                                    Would you accept a nearby office?
                                <RequiredStar />
                                    </label>

                                <select
                                    id="accept_nearby_office"
                                    value={form.data.accept_nearby_office}
                                    onChange={(event) =>
                                        form.setData(
                                            'accept_nearby_office',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClass}
                                >
                                    <option value="">Select</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>

                            <div className="mt-5">
                                <label
                                    htmlFor="transfer_reason"
                                    className={labelClass}
                                >
                                    General reason for requesting a transfer
                                <RequiredStar />
                                    </label>

                                <textarea
                                    id="transfer_reason"
                                    value={form.data.transfer_reason}
                                    onChange={(event) =>
                                        form.setData(
                                            'transfer_reason',
                                            event.target.value,
                                        )
                                    }
                                    rows={4}
                                    className={inputClass}
                                />

                                {errors.transfer_reason && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {errors.transfer_reason}
                                    </p>
                                )}
                            </div>
                        </section>

                        <div className="flex flex-wrap items-center gap-4">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60"
                            >
                                {form.processing
                                    ? 'Saving...'
                                    : 'Save draft'}
                            </button>

                            {form.recentlySuccessful && (
                                <span
                                    role="status"
                                    className="text-sm font-medium text-emerald-700 dark:text-emerald-400"
                                >
                                    Draft saved.
                                </span>
                            )}

                            <Link
                                href={`/transfer-applications/${application.id}/review`}
                                className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium dark:border-zinc-700"
                            >
                                Review application
                            </Link>

                            {form.hasErrors && (
                                <span
                                    role="alert"
                                    className="text-sm text-red-600 dark:text-red-400"
                                >
                                    Please check the highlighted fields.
                                </span>
                            )}
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
