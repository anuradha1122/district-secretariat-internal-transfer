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

type Profile = {
    name_with_initials: string;
    designation: string;
    employee_no: string | null;
    mobile_no: string | null;
    office_id: number;
    service_id: number;
};

type Props = {
    profile: Profile | null;
    offices: Office[];
    services: Service[];
};

type ProfileForm = {
    name_with_initials: string;
    designation: string;
    employee_no: string;
    mobile_no: string;
    office_id: string;
    service_id: string;
};

const labelClass =
    'block text-sm font-medium text-slate-700 dark:text-zinc-200';

const inputClass =
    'mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500';

const errorClass = 'mt-1.5 text-sm text-red-600 dark:text-red-400';

export default function EmployeeProfileEdit({
    profile,
    offices,
    services,
}: Props) {
    const form = useForm<ProfileForm>({
        name_with_initials: profile?.name_with_initials ?? '',
        designation: profile?.designation ?? '',
        employee_no: profile?.employee_no ?? '',
        mobile_no: profile?.mobile_no ?? '',
        office_id: profile ? String(profile.office_id) : '',
        service_id: profile ? String(profile.service_id) : '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        form.put('/employee-profile', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="My Employee Profile" />

            <div className="min-h-full bg-slate-50 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
                <div className="mx-auto max-w-5xl space-y-6 p-4 sm:p-6 lg:p-8">
                    <div>
                        <Link
                            href="/dashboard"
                            className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400"
                        >
                            ← Back to dashboard
                        </Link>

                        <h1 className="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">
                            My Employee Profile
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Enter your current employment information. Your
                            office head will verify these details during the
                            transfer application review.
                        </p>
                    </div>

                    {(offices.length === 0 || services.length === 0) && (
                        <div
                            role="alert"
                            className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            No active{' '}
                            {offices.length === 0 && services.length === 0
                                ? 'offices or services are'
                                : offices.length === 0
                                  ? 'offices are'
                                  : 'services are'}{' '}
                            available. Please contact the administrator.
                        </div>
                    )}

                    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-zinc-800">
                            <h2 className="text-base font-semibold">
                                Employment information
                            </h2>

                            <p className="mt-1 text-sm text-slate-600 dark:text-zinc-400">
                                Fields marked with * are required.
                            </p>
                        </div>

                        <form onSubmit={submit} className="p-5 sm:p-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label
                                        htmlFor="name_with_initials"
                                        className={labelClass}
                                    >
                                        Name with initials *
                                    </label>

                                    <input
                                        id="name_with_initials"
                                        value={form.data.name_with_initials}
                                        onChange={(event) =>
                                            form.setData(
                                                'name_with_initials',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Example: A. B. Perera"
                                        maxLength={255}
                                        className={inputClass}
                                        required
                                    />

                                    {form.errors.name_with_initials && (
                                        <p className={errorClass}>
                                            {form.errors.name_with_initials}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="designation"
                                        className={labelClass}
                                    >
                                        Designation *
                                    </label>

                                    <input
                                        id="designation"
                                        value={form.data.designation}
                                        onChange={(event) =>
                                            form.setData(
                                                'designation',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Example: Development Officer"
                                        maxLength={255}
                                        className={inputClass}
                                        required
                                    />

                                    {form.errors.designation && (
                                        <p className={errorClass}>
                                            {form.errors.designation}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="office_id"
                                        className={labelClass}
                                    >
                                        Current office *
                                    </label>

                                    <select
                                        id="office_id"
                                        value={form.data.office_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'office_id',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                        required
                                    >
                                        <option value="">
                                            Select your current office
                                        </option>

                                        {offices.map((office) => (
                                            <option
                                                key={office.id}
                                                value={office.id}
                                            >
                                                {office.name} ({office.code})
                                            </option>
                                        ))}
                                    </select>

                                    {form.errors.office_id && (
                                        <p className={errorClass}>
                                            {form.errors.office_id}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="service_id"
                                        className={labelClass}
                                    >
                                        Service *
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
                                        required
                                    >
                                        <option value="">
                                            Select your service
                                        </option>

                                        {services.map((service) => (
                                            <option
                                                key={service.id}
                                                value={service.id}
                                            >
                                                {service.name} ({service.code})
                                            </option>
                                        ))}
                                    </select>

                                    {form.errors.service_id && (
                                        <p className={errorClass}>
                                            {form.errors.service_id}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="employee_no"
                                        className={labelClass}
                                    >
                                        Employee number
                                    </label>

                                    <input
                                        id="employee_no"
                                        value={form.data.employee_no}
                                        onChange={(event) =>
                                            form.setData(
                                                'employee_no',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="If available"
                                        maxLength={50}
                                        className={inputClass}
                                    />

                                    {form.errors.employee_no && (
                                        <p className={errorClass}>
                                            {form.errors.employee_no}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="mobile_no"
                                        className={labelClass}
                                    >
                                        Mobile number
                                    </label>

                                    <input
                                        id="mobile_no"
                                        type="tel"
                                        value={form.data.mobile_no}
                                        onChange={(event) =>
                                            form.setData(
                                                'mobile_no',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="If available"
                                        maxLength={20}
                                        className={inputClass}
                                    />

                                    {form.errors.mobile_no && (
                                        <p className={errorClass}>
                                            {form.errors.mobile_no}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="mt-6 border-t border-slate-200 pt-5 dark:border-zinc-800">
                                <button
                                    type="submit"
                                    disabled={
                                        form.processing ||
                                        offices.length === 0 ||
                                        services.length === 0
                                    }
                                    className="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {form.processing
                                        ? 'Saving...'
                                        : 'Save profile'}
                                </button>

                                {form.recentlySuccessful && (
                                    <span
                                        role="status"
                                        className="ml-3 text-sm font-medium text-emerald-700 dark:text-emerald-400"
                                    >
                                        Profile saved.
                                    </span>
                                )}
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}
