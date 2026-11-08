import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type ResetPasswordForm = {
    nic: string;
    reset_code: string;
    password: string;
    password_confirmation: string;
};

const inputClass =
    'mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-500';

const labelClass =
    'block text-sm font-medium text-slate-700 dark:text-zinc-200';

const errorClass = 'mt-1.5 text-sm text-red-600 dark:text-red-400';

export default function ResetEmployeePassword() {
    const form = useForm<ResetPasswordForm>({
        nic: '',
        reset_code: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        form.post('/employee-password-reset', {
            onFinish: () => {
                form.reset('password', 'password_confirmation');
            },
        });
    };

    return (
        <>
            <Head title="Reset Employee Password" />

            <div className="space-y-6">
                <div className="space-y-2 text-center">
                    <h1 className="text-2xl font-semibold text-slate-900 dark:text-zinc-100">
                        Reset your password
                    </h1>

                    <p className="text-sm leading-6 text-slate-600 dark:text-zinc-400">
                        Enter your NIC and the reset code issued by your
                        administrator.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-4">
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
                            placeholder="Enter your NIC"
                            maxLength={12}
                            autoComplete="off"
                            className={inputClass}
                            required
                        />

                        {form.errors.nic && (
                            <p role="alert" className={errorClass}>
                                {form.errors.nic}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="reset_code" className={labelClass}>
                            Password reset code
                        </label>

                        <input
                            id="reset_code"
                            type="text"
                            value={form.data.reset_code}
                            onChange={(event) =>
                                form.setData(
                                    'reset_code',
                                    event.target.value.toUpperCase(),
                                )
                            }
                            placeholder="Enter your reset code"
                            maxLength={24}
                            autoComplete="off"
                            className={inputClass}
                            required
                        />

                        {form.errors.reset_code && (
                            <p role="alert" className={errorClass}>
                                {form.errors.reset_code}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="password" className={labelClass}>
                            New password
                        </label>

                        <input
                            id="password"
                            type="password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            placeholder="Enter your new password"
                            autoComplete="new-password"
                            minLength={12}
                            className={inputClass}
                            required
                        />

                        <p className="mt-1.5 text-xs text-slate-500 dark:text-zinc-400">
                            Use at least 12 characters.
                        </p>

                        {form.errors.password && (
                            <p role="alert" className={errorClass}>
                                {form.errors.password}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="password_confirmation"
                            className={labelClass}
                        >
                            Confirm new password
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
                            placeholder="Enter your new password again"
                            autoComplete="new-password"
                            className={inputClass}
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {form.processing
                            ? 'Resetting password...'
                            : 'Reset password'}
                    </button>
                </form>

                <p className="text-center text-sm text-slate-600 dark:text-zinc-400">
                    Remember your password?{' '}
                    <Link
                        href="/login"
                        className="font-medium text-blue-700 hover:underline dark:text-blue-400"
                    >
                        Back to login
                    </Link>
                </p>
            </div>
        </>
    );
}
