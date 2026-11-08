import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

type User = {
    id: number;
    name: string;
    email: string | null;
};

type Office = {
    id: number;
    code: string;
    name: string;
    type: string;
    is_active?: boolean;
};

type Assignment = {
    id: number;
    is_active: boolean;
    assigned_at: string | null;
    revoked_at: string | null;
    user: User;
    office: Office;
    assigned_by: Pick<User, 'id' | 'name'> | null;
    revoked_by: Pick<User, 'id' | 'name'> | null;
};

type Props = {
    reviewers: User[];
    offices: Office[];
    assignments: Assignment[];
};

function officeType(type: string): string {
    if (type === 'district_secretariat') {
        return 'District Secretariat';
    }

    if (type === 'divisional_secretariat') {
        return 'Divisional Secretariat';
    }

    return type;
}

export default function OfficeReviewerAssignments({
    reviewers,
    offices,
    assignments,
}: Props) {
    const form = useForm({
        user_id: '',
        office_id: '',
    });

    const [revokingId, setRevokingId] = useState<number | null>(null);

    function submit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post('/office-reviewer-assignments', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function revoke(assignment: Assignment) {
        const confirmed = window.confirm(
            `Revoke ${assignment.user.name}'s assignment to ${assignment.office.name}?`,
        );

        if (! confirmed) {
            return;
        }

        setRevokingId(assignment.id);

        form.delete(`/office-reviewer-assignments/${assignment.id}`, {
            preserveScroll: true,
            onFinish: () => setRevokingId(null),
        });
    }

    const inputClass =
        'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100';

    return (
        <>
            <Head title="Office Head Assignments" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Office Head Assignments
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Assign an Office Head to each office they are authorized to review.
                    </p>
                </div>

                <div className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Assign an office
                    </h2>

                    {reviewers.length === 0 && (
                        <p className="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                            No users currently have the Office Head role.
                            Assign that role to a user before creating an office assignment.
                        </p>
                    )}

                    {offices.length === 0 && (
                        <p className="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                            No active offices are available.
                        </p>
                    )}

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="user_id"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Office Head <span className="text-red-600">*</span>
                                </label>

                                <select
                                    id="user_id"
                                    value={form.data.user_id}
                                    onChange={(event) =>
                                        form.setData('user_id', event.target.value)
                                    }
                                    className={inputClass}
                                    required
                                >
                                    <option value="">Select an Office Head</option>

                                    {reviewers.map((reviewer) => (
                                        <option key={reviewer.id} value={reviewer.id}>
                                            {reviewer.name}
                                            {reviewer.email ? ` (${reviewer.email})` : ''}
                                        </option>
                                    ))}
                                </select>

                                {form.errors.user_id && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.user_id}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="office_id"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    Office <span className="text-red-600">*</span>
                                </label>

                                <select
                                    id="office_id"
                                    value={form.data.office_id}
                                    onChange={(event) =>
                                        form.setData('office_id', event.target.value)
                                    }
                                    className={inputClass}
                                    required
                                >
                                    <option value="">Select an office</option>

                                    {offices.map((office) => (
                                        <option key={office.id} value={office.id}>
                                            {office.code} — {office.name} (
                                            {officeType(office.type)})
                                        </option>
                                    ))}
                                </select>

                                {form.errors.office_id && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.office_id}
                                    </p>
                                )}
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={
                                form.processing ||
                                reviewers.length === 0 ||
                                offices.length === 0
                            }
                            className="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {form.processing ? 'Saving...' : 'Assign Office'}
                        </button>
                    </form>
                </div>

                <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Assignments
                        </h2>
                    </div>

                    {assignments.length === 0 ? (
                        <p className="p-5 text-sm text-gray-600 dark:text-gray-400">
                            No office head assignments have been created yet.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    <tr>
                                        <th className="px-4 py-3">Office Head</th>
                                        <th className="px-4 py-3">Office</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Last action</th>
                                        <th className="px-4 py-3">Action</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {assignments.map((assignment) => (
                                        <tr
                                            key={assignment.id}
                                            className="text-gray-900 dark:text-gray-100"
                                        >
                                            <td className="px-4 py-3">
                                                <div>{assignment.user.name}</div>

                                                {assignment.user.email && (
                                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                                        {assignment.user.email}
                                                    </div>
                                                )}
                                            </td>

                                            <td className="px-4 py-3">
                                                <div>
                                                    {assignment.office.code} —{' '}
                                                    {assignment.office.name}
                                                </div>

                                                <div className="text-xs text-gray-500 dark:text-gray-400">
                                                    {officeType(assignment.office.type)}
                                                    {assignment.office.is_active === false
                                                        ? ' · Office inactive'
                                                        : ''}
                                                </div>
                                            </td>

                                            <td className="px-4 py-3">
                                                {assignment.is_active ? (
                                                    <span className="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Active
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                        Revoked
                                                    </span>
                                                )}
                                            </td>

                                            <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                                {assignment.is_active ? (
                                                    <>
                                                        Assigned by{' '}
                                                        {assignment.assigned_by?.name ?? 'Unknown'}
                                                        <br />
                                                        {assignment.assigned_at ?? '—'}
                                                    </>
                                                ) : (
                                                    <>
                                                        Revoked by{' '}
                                                        {assignment.revoked_by?.name ?? 'Unknown'}
                                                        <br />
                                                        {assignment.revoked_at ?? '—'}
                                                    </>
                                                )}
                                            </td>

                                            <td className="px-4 py-3">
                                                {assignment.is_active && (
                                                    <button
                                                        type="button"
                                                        onClick={() => revoke(assignment)}
                                                        disabled={revokingId !== null}
                                                        className="rounded-md border border-red-300 px-3 py-1 text-sm text-red-700 hover:bg-red-50 disabled:opacity-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950"
                                                    >
                                                        {revokingId === assignment.id
                                                            ? 'Revoking...'
                                                            : 'Revoke'}
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
