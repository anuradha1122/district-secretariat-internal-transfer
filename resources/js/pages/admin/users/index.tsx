import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type ManagedUser = {
    id: number;
    name: string;
    email: string | null;
    email_verified: boolean;
    is_employee_account: boolean;
    roles: string[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    users: {
        data: ManagedUser[];
        links: PaginationLink[];
        total: number;
    };
    availableRoles: string[];
    filters: {
        search: string;
    };
    canManage: boolean;
    currentUserId: number;
};

const inputClass =
    'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100';

const labelClass =
    'block text-sm font-medium text-gray-700 dark:text-gray-200';

function RoleEditor({
    user,
    availableRoles,
}: {
    user: ManagedUser;
    availableRoles: string[];
}) {
    const form = useForm({
        roles: user.roles.filter((role) => availableRoles.includes(role)),
    });

    function toggleRole(role: string) {
        const selected = form.data.roles.includes(role);

        form.setData(
            'roles',
            selected
                ? form.data.roles.filter((item) => item !== role)
                : [...form.data.roles, role],
        );
    }

    function submit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const confirmed = window.confirm(
            `Save staff role changes for ${user.name}? Removing Office Head will revoke their office assignments.`,
        );

        if (!confirmed) {
            return;
        }

        form.put(`/user-management/${user.id}/roles`, {
            preserveScroll: true,
        });
    }

    return (
        <form onSubmit={submit} className="space-y-3">
            <fieldset>
                <legend className="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Staff roles
                </legend>

                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                    {availableRoles.map((role) => (
                        <label
                            key={role}
                            className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"
                        >
                            <input
                                type="checkbox"
                                checked={form.data.roles.includes(role)}
                                onChange={() => toggleRole(role)}
                                className="rounded border-gray-300 text-blue-700 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                            />

                            {role}
                        </label>
                    ))}
                </div>
            </fieldset>

            {form.errors.roles && (
                <p className="text-sm text-red-600 dark:text-red-400">
                    {form.errors.roles}
                </p>
            )}

            <button
                type="submit"
                disabled={form.processing}
                className="rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
            >
                {form.processing ? 'Saving...' : 'Save Roles'}
            </button>
        </form>
    );
}

export default function UserManagementIndex({
    users,
    availableRoles,
    filters,
    canManage,
    currentUserId,
}: Props) {
    const [search, setSearch] = useState(filters.search);

    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [] as string[],
        email_verified: false,
    });

    function toggleNewUserRole(role: string) {
        form.setData(
            'roles',
            form.data.roles.includes(role)
                ? form.data.roles.filter((item) => item !== role)
                : [...form.data.roles, role],
        );
    }

    function createUser(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post('/user-management', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    }

    function searchUsers(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        router.get(
            '/user-management',
            { search },
            {
                preserveState: true,
                replace: true,
            },
        );
    }

    return (
        <>
            <Head title="User Management" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        User Management
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Create staff accounts and manage staff roles.
                    </p>
                </div>

                {canManage && (
                    <section className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Create staff user
                        </h2>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Employees applying with NIC should continue to use the employee
                            activation process.
                        </p>

                        <form onSubmit={createUser} className="mt-4 space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label htmlFor="name" className={labelClass}>
                                        Name <span className="text-red-600">*</span>
                                    </label>

                                    <input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(event) =>
                                            form.setData('name', event.target.value)
                                        }
                                        className={inputClass}
                                        required
                                    />

                                    {form.errors.name && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {form.errors.name}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="email" className={labelClass}>
                                        Email <span className="text-red-600">*</span>
                                    </label>

                                    <input
                                        id="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(event) =>
                                            form.setData('email', event.target.value)
                                        }
                                        className={inputClass}
                                        required
                                    />

                                    {form.errors.email && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {form.errors.email}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="password" className={labelClass}>
                                        Password <span className="text-red-600">*</span>
                                    </label>

                                    <input
                                        id="password"
                                        type="password"
                                        autoComplete="new-password"
                                        value={form.data.password}
                                        onChange={(event) =>
                                            form.setData('password', event.target.value)
                                        }
                                        className={inputClass}
                                        required
                                    />

                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        At least 12 characters, including letters and numbers.
                                    </p>

                                    {form.errors.password && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {form.errors.password}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="password_confirmation"
                                        className={labelClass}
                                    >
                                        Confirm password <span className="text-red-600">*</span>
                                    </label>

                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        value={form.data.password_confirmation}
                                        onChange={(event) =>
                                            form.setData(
                                                'password_confirmation',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                        required
                                    />
                                </div>
                            </div>

                            <fieldset>
                                <legend className={labelClass}>
                                    Staff roles <span className="text-red-600">*</span>
                                </legend>

                                <div className="mt-2 flex flex-wrap gap-x-5 gap-y-2">
                                    {availableRoles.map((role) => (
                                        <label
                                            key={role}
                                            className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={form.data.roles.includes(role)}
                                                onChange={() => toggleNewUserRole(role)}
                                                className="rounded border-gray-300 text-blue-700 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                            />

                                            {role}
                                        </label>
                                    ))}
                                </div>

                                {form.errors.roles && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.roles}
                                    </p>
                                )}
                            </fieldset>

                            <div>
                                <label className="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input
                                        type="checkbox"
                                        checked={form.data.email_verified}
                                        onChange={(event) =>
                                            form.setData(
                                                'email_verified',
                                                event.target.checked,
                                            )
                                        }
                                        className="mt-1 rounded border-gray-300 text-blue-700 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                        required
                                    />

                                    <span>
                                        I have independently verified this email address
                                        and authorized this account.
                                    </span>
                                </label>

                                {form.errors.email_verified && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.email_verified}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
                            >
                                {form.processing ? 'Creating...' : 'Create User'}
                            </button>
                        </form>
                    </section>
                )}

                <section className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="space-y-4 border-b border-gray-200 p-5 dark:border-gray-700">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Users
                            </h2>

                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Total matching users: {users.total}
                            </p>
                        </div>

                        <form onSubmit={searchUsers} className="flex flex-wrap gap-2">
                            <input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search by name or email"
                                aria-label="Search users"
                                className={`${inputClass} mt-0 max-w-sm`}
                            />

                            <button
                                type="submit"
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                Search
                            </button>

                            <Link
                                href="/user-management"
                                className="rounded-md px-3 py-2 text-sm text-blue-700 hover:underline dark:text-blue-400"
                            >
                                Clear
                            </Link>
                        </form>
                    </div>

                    {users.data.length === 0 ? (
                        <p className="p-5 text-sm text-gray-600 dark:text-gray-400">
                            No users found.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-200 dark:divide-gray-700">
                            {users.data.map((user) => {
                                const protectedUser =
                                    user.id === currentUserId ||
                                    user.roles.includes('Super Admin');

                                return (
                                    <div key={user.id} className="space-y-4 p-5">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {user.name}
                                                </h3>

                                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {user.email ?? 'No email'} · ID: {user.id}
                                                </p>

                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {user.is_employee_account
                                                        ? 'Linked employee account'
                                                        : 'Staff account'}
                                                </p>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                {user.roles.length === 0 ? (
                                                    <span className="text-sm text-gray-500 dark:text-gray-400">
                                                        No roles
                                                    </span>
                                                ) : (
                                                    user.roles.map((role) => (
                                                        <span
                                                            key={role}
                                                            className="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                        >
                                                            {role}
                                                        </span>
                                                    ))
                                                )}
                                            </div>
                                        </div>

                                        {canManage && !protectedUser && (
                                            <RoleEditor
                                                key={`${user.id}-${user.roles.join('|')}`}
                                                user={user}
                                                availableRoles={availableRoles}
                                            />
                                        )}

                                        {canManage && protectedUser && (
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Roles for this account cannot be changed here.
                                            </p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {users.links.length > 3 && (
                        <div className="flex flex-wrap gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                            {users.links.map((link, index) => {
                                const label = link.label
                                    .replace('&laquo;', '«')
                                    .replace('&raquo;', '»');

                                return link.url ? (
                                    <Link
                                        key={index}
                                        href={link.url}
                                        className={`rounded border px-3 py-1 text-sm ${
                                            link.active
                                                ? 'border-blue-700 bg-blue-700 text-white'
                                                : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-200'
                                        }`}
                                    >
                                        {label}
                                    </Link>
                                ) : (
                                    <span
                                        key={index}
                                        className="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400 dark:border-gray-700"
                                    >
                                        {label}
                                    </span>
                                );
                            })}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
