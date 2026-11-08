import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Props = {
    issuedCode: {
        nic: string;
        code: string;
    } | null;
};

export default function EmployeePasswordResetCodes({
    issuedCode,
}: Props) {
    const form = useForm({
        nic: '',
    });

    const issue: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();

        if (
            !window.confirm(
                'Have you verified this employee’s identity? Issuing a new code replaces any previous unused reset code.',
            )
        ) {
            return;
        }

        form.post('/employee-reset-codes', {
            onSuccess: () => form.reset(),
        });
    };

    const revoke = () => {
        if (!form.data.nic.trim()) {
            form.setError('nic', 'Enter the employee NIC first.');
            return;
        }

        if (!window.confirm('Revoke this employee’s unused reset code?')) {
            return;
        }

        form.delete('/employee-reset-codes', {
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="Employee Password Reset Codes" />

            <div className="min-h-full bg-slate-50 p-4 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100 sm:p-6 lg:p-8">
                <div className="mx-auto max-w-3xl space-y-6">
                    <div>
                        <Link
                            href="/dashboard"
                            className="text-sm text-blue-700 hover:underline dark:text-blue-400"
                        >
                            ← Dashboard
                        </Link>

                        <h1 className="mt-3 text-2xl font-semibold">
                            Employee Password Reset
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Verify the employee’s identity before issuing a
                            reset code. Deliver the code privately.
                        </p>
                    </div>

                    {issuedCode && (
                        <div
                            role="status"
                            className="rounded-xl border border-emerald-300 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"
                        >
                            <p className="font-semibold">
                                Password reset code issued
                            </p>

                            <p className="mt-2 text-sm">
                                NIC: <strong>{issuedCode.nic}</strong>
                            </p>

                            <p className="mt-2 break-all font-mono text-lg font-bold">
                                {issuedCode.code}
                            </p>

                            <p className="mt-3 text-sm leading-6">
                                This code is displayed once, expires in 24
                                hours and can be used only once. Give it
                                privately to the verified employee.
                            </p>
                        </div>
                    )}

                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 className="text-base font-semibold">
                            Issue or revoke a reset code
                        </h2>

                        <form onSubmit={issue} className="mt-5 space-y-4">
                            <div>
                                <label
                                    htmlFor="nic"
                                    className="block text-sm font-medium text-slate-700 dark:text-zinc-200"
                                >
                                    Employee NIC
                                </label>

                                <input
                                    id="nic"
                                    type="text"
                                    value={form.data.nic}
                                    onChange={(event) =>
                                        form.setData(
                                            'nic',
                                            event.target.value,
                                        )
                                    }
                                    maxLength={12}
                                    required
                                    className="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                />

                                {form.errors.nic && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.nic}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60"
                                >
                                    {form.processing
                                        ? 'Processing...'
                                        : 'Issue reset code'}
                                </button>

                                <button
                                    type="button"
                                    onClick={revoke}
                                    disabled={form.processing}
                                    className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    Revoke unused code
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}
