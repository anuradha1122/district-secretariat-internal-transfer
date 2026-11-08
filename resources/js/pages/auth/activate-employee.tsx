import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type ActivationForm = {
    nic: string;
    activation_code: string;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

const inputClass =
    'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100';

const labelClass =
    'block text-sm font-medium text-slate-700 dark:text-zinc-200';

const errorClass =
    'mt-1 text-sm text-red-600 dark:text-red-400';

export default function ActivateEmployee() {
    const form = useForm<ActivationForm>({
        nic: '',
        activation_code: '',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        form.post('/activate-employee', {
            onFinish: () => {
                form.reset('password', 'password_confirmation');
            },
        });
    };

    return (
        <>
            <Head title="Activate Employee Account" />

            <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
                <div className="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
                    <h1 className="text-2xl font-semibold">
                        Activate your account
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                        Enter your NIC and the activation code issued by your
                        administrator. Then create your login details.
                    </p>

                    <form onSubmit={submit} className="mt-6 space-y-4">
                        <div>
                            <label htmlFor="nic" className={labelClass}>
                                NIC number
                            </label>

                            <input
                                id="nic"
                                type="text"
                                value={form.data.nic}
                                onChange={(event) =>
                                    form.setData('nic', event.target.value)
                                }
                                className={inputClass}
                                autoComplete="off"
                                required
                            />

                            {form.errors.nic && (
                                <p className={errorClass}>{form.errors.nic}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="activation_code"
                                className={labelClass}
                            >
                                Activation code
                            </label>

                            <input
                                id="activation_code"
                                type="text"
                                value={form.data.activation_code}
                                onChange={(event) =>
                                    form.setData(
                                        'activation_code',
                                        event.target.value.toUpperCase(),
                                    )
                                }
                                className={inputClass}
                                autoComplete="off"
                                maxLength={24}
                                required
                            />

                            {form.errors.activation_code && (
                                <p className={errorClass}>
                                    {form.errors.activation_code}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="name" className={labelClass}>
                                Name
                            </label>

                            <input
                                id="name"
                                type="text"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                className={inputClass}
                                autoComplete="name"
                                required
                            />

                            {form.errors.name && (
                                <p className={errorClass}>{form.errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="email" className={labelClass}>
                                Email address <span className="font-normal">(optional)</span>
                            </label>

                            <input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                className={inputClass}
                                autoComplete="email"
                            />
                            <p className="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                If you provide an email address, you will need to verify it.
                                You can log in using your NIC.
                            </p>

                            {form.errors.email && (
                                <p className={errorClass}>{form.errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className={labelClass}>
                                Password
                            </label>

                            <input
                                id="password"
                                type="password"
                                value={form.data.password}
                                onChange={(event) =>
                                    form.setData(
                                        'password',
                                        event.target.value,
                                    )
                                }
                                className={inputClass}
                                autoComplete="new-password"
                                minLength={12}
                                required
                            />

                            <p className="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                Use at least 12 characters.
                            </p>

                            {form.errors.password && (
                                <p className={errorClass}>
                                    {form.errors.password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className={labelClass}
                            >
                                Confirm password
                            </label>

                            <input
                                id="password_confirmation"
                                type="password"
                                value={form.data.password_confirmation}
                                onChange={(event) =>
                                    form.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                                className={inputClass}
                                autoComplete="new-password"
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60"
                        >
                            {form.processing
                                ? 'Activating...'
                                : 'Activate account'}
                        </button>
                    </form>

                    <p className="mt-5 text-center text-sm text-slate-600 dark:text-zinc-400">
                        Already have an account?{' '}
                        <Link
                            href="/login"
                            className="font-medium text-blue-700 hover:underline dark:text-blue-400"
                        >
                            Log in
                        </Link>
                    </p>
                </div>
            </main>
        </>
    );
}
