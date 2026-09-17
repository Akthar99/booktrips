import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { reply as adminReply, status as adminStatus } from '@/actions/App/Http/Controllers/Admin/AdminSupportController';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import { Input, Textarea } from '@/components/booktrips/field';
import Pagination from '@/components/booktrips/pagination';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type TicketRow = {
    id: number;
    subject: string;
    category_label: string;
    status: string;
    status_label: string;
    last_message_at: string | null;
    user: { id: number; name: string; email: string; role: string } | null;
};

type TicketThread = TicketRow & {
    messages: Array<{ id: number; body: string; is_staff: boolean; author: string; created_at: string | null }>;
};

type AdminSupportProps = {
    tickets: Paginated<TicketRow>;
    selected: TicketThread | null;
    filters: { status: string; q: string };
    counts: { open: number; awaiting_admin: number };
};

const STATUS_TONES: Record<string, string> = {
    open: 'bg-brand-50 text-brand-950',
    awaiting_admin: 'bg-orange-50 text-warn',
    awaiting_user: 'bg-brand-50 text-brand-900',
    resolved: 'bg-cream-dark text-muted',
};

const AdminSupport: InertiaComponent<AdminSupportProps> = ({ tickets, selected, filters, counts }) => {
    const [q, setQ] = useState(filters.q);
    const reply = useForm({ body: '' });

    function filter(patch: Record<string, string>) {
        router.get('/admin/support', { ...filters, ...patch }, { preserveScroll: true, preserveState: true });
    }

    function submitReply(event: React.FormEvent) {
        event.preventDefault();

        if (!selected) {
            return;
        }

        reply.post(adminReply.url(selected.id), {
            preserveScroll: true,
            onSuccess: () => reply.reset(),
        });
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-2xl">Support inbox</h2>
                <span className="text-[13px] text-muted">
                    {counts.open} open · {counts.awaiting_admin} waiting on us
                </span>
            </div>

            <form
                className="mb-4 flex flex-wrap gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    filter({ q });
                }}
            >
                <Input
                    className="min-w-[200px] flex-1"
                    placeholder="Search subject, name or email"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                />
                <select
                    className="input-base w-auto cursor-pointer"
                    value={filters.status}
                    onChange={(event) => filter({ status: event.target.value })}
                >
                    <option value="open">Open threads</option>
                    <option value="awaiting_admin">Waiting on us</option>
                    <option value="resolved">Resolved</option>
                    <option value="all">Everything</option>
                </select>
                <Button type="submit">Search</Button>
            </form>

            <div className="grid gap-5 lg:grid-cols-[360px_1fr]">
                <div className="h-fit overflow-hidden rounded-2xl border border-line bg-white">
                    {tickets.data.length === 0 ? (
                        <p className="px-4 py-3 text-[13px] text-muted">Nothing here.</p>
                    ) : null}
                    {tickets.data.map((ticket) => (
                        <Link
                            key={ticket.id}
                            href={`/admin/support?status=${filters.status}&ticket=${ticket.id}`}
                            preserveScroll
                            className={cn(
                                'block border-b border-line px-4 py-3 transition hover:bg-cream',
                                selected?.id === ticket.id && 'bg-cream',
                            )}
                        >
                            <strong className="block text-[13px] text-brand-900">{ticket.subject}</strong>
                            <span className="text-[12px] text-muted">
                                {ticket.user?.name} · {ticket.user?.email}
                            </span>
                            <div className="mt-1.5 flex items-center gap-2">
                                <span
                                    className={cn(
                                        'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                        STATUS_TONES[ticket.status] ?? 'bg-cream-dark text-muted',
                                    )}
                                >
                                    {ticket.status_label}
                                </span>
                                <span className="text-[11px] text-muted">
                                    {ticket.last_message_at
                                        ? new Date(ticket.last_message_at).toLocaleString('en-GB')
                                        : ''}
                                </span>
                            </div>
                        </Link>
                    ))}
                    <div className="p-3">
                        <Pagination
                            page={tickets.current_page}
                            lastPage={tickets.last_page}
                            total={tickets.total}
                        />
                    </div>
                </div>

                {selected ? (
                    <div className="rounded-2xl border border-line bg-white p-5">
                        <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="text-2xl">{selected.subject}</h2>
                                <p className="text-[13px] text-muted">
                                    {selected.user?.name} · {selected.user?.email} · {selected.category_label}
                                    {selected.user?.role === 'business' ? ' · partner' : ''}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                {selected.status === 'resolved' ? (
                                    <button
                                        type="button"
                                        className="cursor-pointer rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold text-brand-900"
                                        onClick={() =>
                                            router.patch(adminStatus.url(selected.id), { status: 'awaiting_admin' })
                                        }
                                    >
                                        Reopen
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        className="cursor-pointer rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold text-brand-900"
                                        onClick={() => router.patch(adminStatus.url(selected.id), { status: 'resolved' })}
                                    >
                                        Mark resolved
                                    </button>
                                )}
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col gap-2.5">
                            {selected.messages.map((message) => (
                                <div
                                    key={message.id}
                                    className={cn(
                                        'rounded-xl px-3.5 py-2.5',
                                        message.is_staff ? 'bg-brand-50' : 'bg-cream',
                                    )}
                                >
                                    <div className="mb-1 flex items-center justify-between gap-2 text-[11px] font-bold tracking-wide text-muted uppercase">
                                        <span>{message.author}</span>
                                        <span>
                                            {message.created_at
                                                ? new Date(message.created_at).toLocaleString('en-GB')
                                                : ''}
                                        </span>
                                    </div>
                                    <p className="text-[13px] whitespace-pre-line">{message.body}</p>
                                </div>
                            ))}
                        </div>

                        <form onSubmit={submitReply}>
                            <Textarea
                                placeholder="Reply to the user — they get an email and an in-app alert…"
                                value={reply.data.body}
                                onChange={(event) => reply.setData('body', event.target.value)}
                            />
                            {Object.values(reply.errors)[0] ? (
                                <Alert tone="error">{Object.values(reply.errors)[0]}</Alert>
                            ) : null}
                            <Button type="submit" disabled={reply.processing}>
                                {reply.processing ? 'Sending…' : 'Send reply'}
                            </Button>
                        </form>
                    </div>
                ) : (
                    <div className="rounded-2xl border border-line bg-white p-6 text-sm text-muted">
                        Pick a thread to read the conversation.
                    </div>
                )}
            </div>
        </div>
    );
};

AdminSupport.layout = withAppLayout;

export default AdminSupport;
