import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { update as updateUser } from '@/actions/App/Http/Controllers/Admin/AdminUserController';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import { Input, Select } from '@/components/booktrips/field';
import Pagination from '@/components/booktrips/pagination';
import StatusBadge from '@/components/booktrips/status-badge';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import type { Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type AdminUserRow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    role: string;
    email_verified: boolean;
    active: boolean;
    booking_count: number;
    business_name: string | null;
    business_approved: boolean | null;
};

type UsersProps = {
    users: Paginated<AdminUserRow>;
    filters: { q: string; role: string };
};

const AdminUsers: InertiaComponent<UsersProps> = ({ users, filters }) => {
    const [q, setQ] = useState(filters.q);
    const [role, setRole] = useState(filters.role || 'all');
    const [suspendTarget, setSuspendTarget] = useState<AdminUserRow | null>(
        null,
    );
    const [busy, setBusy] = useState(false);

    function load(next: { q?: string; role?: string } = {}) {
        router.get(
            '/admin/users',
            {
                q: (next.q ?? q) || undefined,
                role:
                    (next.role ?? role) !== 'all'
                        ? (next.role ?? role)
                        : undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    function patch(id: number, body: Record<string, boolean>) {
        setBusy(true);
        router.patch(updateUser.url(id), body, {
            preserveScroll: true,
            onFinish: () => {
                setBusy(false);
                setSuspendTarget(null);
            },
        });
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <h2 className="mb-3 text-2xl">Users</h2>
            <form
                className="my-3 flex flex-wrap items-center gap-2.5"
                onSubmit={(event) => {
                    event.preventDefault();
                    load();
                }}
            >
                <Input
                    className="min-w-50 flex-1"
                    placeholder="Search name, email, phone"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                />
                <Select
                    className="w-auto"
                    value={role}
                    onChange={(event) => {
                        setRole(event.target.value);
                        load({ role: event.target.value });
                    }}
                >
                    <option value="all">All roles</option>
                    <option value="user">Travellers</option>
                    <option value="business">Partners</option>
                    <option value="admin">Admins</option>
                </Select>
                <button
                    type="submit"
                    className="bg-brand-800 hover:bg-brand-900 cursor-pointer rounded-full px-4 py-2 text-[13px] font-bold text-white transition"
                >
                    Search
                </button>
            </form>
            <p className="text-muted mb-3 text-[13px]">{users.total} shown</p>
            <div className="border-line overflow-x-auto rounded-2xl border bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-muted text-[11px] tracking-wide uppercase">
                            <th className="px-3.5 py-3 font-bold">Name</th>
                            <th className="px-3.5 py-3 font-bold">Email</th>
                            <th className="px-3.5 py-3 font-bold">Role</th>
                            <th className="px-3.5 py-3 font-bold">Verified</th>
                            <th className="px-3.5 py-3 font-bold">Bookings</th>
                            <th className="px-3.5 py-3 font-bold">Status</th>
                            <th className="px-3.5 py-3 text-right font-bold">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.map((user) => (
                            <tr key={user.id} className="border-line border-t">
                                <td className="px-3.5 py-3 text-sm">
                                    {user.name}
                                    {user.business_name ? (
                                        <div className="text-muted text-xs">
                                            {user.business_name}
                                        </div>
                                    ) : null}
                                </td>
                                <td className="px-3.5 py-3 text-sm">
                                    {user.email}
                                    <br />
                                    <span className="text-muted">
                                        {user.phone}
                                    </span>
                                </td>
                                <td className="px-3.5 py-3 text-sm capitalize">
                                    {user.role}
                                </td>
                                <td className="px-3.5 py-3 text-sm">
                                    {user.email_verified ? 'Yes' : 'No'}
                                </td>
                                <td className="px-3.5 py-3 text-sm">
                                    {user.booking_count}
                                </td>
                                <td className="px-3.5 py-3">
                                    <StatusBadge
                                        status={
                                            user.active ? 'active' : 'suspended'
                                        }
                                    />
                                </td>
                                <td className="px-3.5 py-3 text-right whitespace-nowrap">
                                    <Link
                                        className="text-brand-800 mr-3 text-[13px] font-bold"
                                        href={`/admin/travellers/${user.id}`}
                                    >
                                        History
                                    </Link>
                                    {!user.email_verified ? (
                                        <button
                                            type="button"
                                            className="text-brand-800 cursor-pointer border-0 bg-transparent text-[13px] font-bold hover:underline"
                                            onClick={() =>
                                                patch(user.id, {
                                                    email_verified: true,
                                                })
                                            }
                                        >
                                            Mark verified
                                        </button>
                                    ) : null}
                                    {user.role !== 'admin' ? (
                                        user.active ? (
                                            <button
                                                type="button"
                                                className="text-danger ml-3.5 cursor-pointer border-0 bg-transparent text-[13px] font-bold hover:underline"
                                                onClick={() =>
                                                    setSuspendTarget(user)
                                                }
                                            >
                                                Suspend
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                className="text-brand-800 ml-3.5 cursor-pointer border-0 bg-transparent text-[13px] font-bold hover:underline"
                                                onClick={() =>
                                                    patch(user.id, {
                                                        active: true,
                                                    })
                                                }
                                            >
                                                Restore
                                            </button>
                                        )
                                    ) : null}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination
                page={users.current_page}
                lastPage={users.last_page}
                total={users.total}
            />

            <ConfirmDialog
                open={Boolean(suspendTarget)}
                title="Suspend this user?"
                message={`${suspendTarget?.name || 'This user'} will not be able to log in until restored.`}
                confirmLabel="Suspend user"
                danger
                busy={busy}
                onConfirm={() =>
                    suspendTarget && patch(suspendTarget.id, { active: false })
                }
                onCancel={() => setSuspendTarget(null)}
            />
        </div>
    );
};

AdminUsers.layout = withAppLayout;

export default AdminUsers;
