import { useEffect, useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { MessageSquarePlus } from 'lucide-react';
import {
    reply as replyToTicket,
    status as ticketStatus,
    store as storeTicket,
} from '@/actions/App/Http/Controllers/SupportTicketController';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import { Field, Input, Select, Textarea } from '@/components/booktrips/field';
import { withAppLayout } from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { InertiaComponent } from '@/types/inertia';

type TicketSummary = {
    id: number;
    subject: string;
    category_label: string;
    status: string;
    status_label: string;
    last_message_at: string | null;
    created_at: string | null;
};

type TicketThread = TicketSummary & {
    messages: Array<{
        id: number;
        body: string;
        is_staff: boolean;
        author: string;
        created_at: string | null;
    }>;
};

type SupportProps = {
    tickets: TicketSummary[];
    selected: TicketThread | null;
    categories: Array<{ value: string; label: string }>;
};

const STATUS_TONES: Record<string, string> = {
    open: 'bg-brand-50 text-brand-950',
    awaiting_admin: 'bg-orange-50 text-warn',
    awaiting_user: 'bg-brand-50 text-brand-900',
    resolved: 'bg-cream-dark text-muted',
};

const Support: InertiaComponent<SupportProps> = ({ tickets, selected, categories }) => {
    const [creating, setCreating] = useState(tickets.length === 0 && !selected);

    const form = useForm({
        subject: '',
        category: categories[0]?.value ?? 'other',
        body: '',
    });
    const reply = useForm({ body: '' });

    useEffect(() => {
        setCreating(tickets.length === 0 && !selected);
    }, [tickets.length, selected]);

    function submitTicket(event: React.FormEvent) {
        event.preventDefault();
        form.post(storeTicket.url(), {
            onSuccess: () => {
                form.reset();
                setCreating(false);
            },
        });
    }

    function submitReply(event: React.FormEvent) {
        event.preventDefault();

        if (!selected) {
            return;
        }

        reply.post(replyToTicket.url(selected.id), {
            preserveScroll: true,
            onSuccess: () => reply.reset(),
        });
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <div className="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h1 className="text-4xl">Help &amp; support</h1>
                    <p className="text-muted">
                        Ask the BookTrips team anything — bookings, payments, your account or the partner tools.
                        We reply here and by email.
                    </p>
                </div>
                {!creating ? (
                    <button
                        type="button"
                        className="inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                        onClick={() => setCreating(true)}
                    >
                        <MessageSquarePlus size={16} /> New request
                    </button>
                ) : null}
            </div>

            {creating ? (
                <form onSubmit={submitTicket} className="mb-5 rounded-card border border-line bg-white p-6">
                    <h2 className="mb-3 text-2xl">New request</h2>
                    <Field label="Subject">
                        <Input
                            required
                            placeholder="Short summary of the problem"
                            value={form.data.subject}
                            onChange={(event) => form.setData('subject', event.target.value)}
                        />
                        {form.errors.subject ? <Alert tone="error">{form.errors.subject}</Alert> : null}
                    </Field>
                    <Field label="What is it about?">
                        <Select
                            value={form.data.category}
                            onChange={(event) => form.setData('category', event.target.value)}
                        >
                            {categories.map((category) => (
                                <option key={category.value} value={category.value}>
                                    {category.label}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Details">
                        <Textarea
                            required
                            placeholder="What happened, when, and what would you like us to do?"
                            value={form.data.body}
                            onChange={(event) => form.setData('body', event.target.value)}
                        />
                        {form.errors.body ? <Alert tone="error">{form.errors.body}</Alert> : null}
                    </Field>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Sending…' : 'Send to BookTrips'}
                        </Button>
                        {tickets.length ? (
                            <button
                                type="button"
                                className="cursor-pointer rounded-full border border-line bg-white px-4 py-2.5 text-sm font-bold text-brand-900"
                                onClick={() => setCreating(false)}
                            >
                                Cancel
                            </button>
                        ) : null}
                    </div>
                </form>
            ) : null}

            <div className="grid gap-5 lg:grid-cols-[320px_1fr]">
                <div className="h-fit rounded-card border border-line bg-white">
                    <h2 className="border-b border-line px-4 py-3 text-sm font-bold">Your requests</h2>
                    {tickets.length === 0 ? (
                        <p className="px-4 py-3 text-[13px] text-muted">Nothing yet.</p>
                    ) : null}
                    {tickets.map((ticket) => (
                        <Link
                            key={ticket.id}
                            href={`/support?ticket=${ticket.id}`}
                            className={cn(
                                'block border-b border-line px-4 py-3 transition hover:bg-cream',
                                selected?.id === ticket.id && 'bg-cream',
                            )}
                        >
                            <strong className="block text-[13px] text-brand-900">{ticket.subject}</strong>
                            <span className="text-[12px] text-muted">{ticket.category_label}</span>
                            <span
                                className={cn(
                                    'mt-1.5 inline-block rounded-full px-2 py-0.5 text-[11px] font-bold',
                                    STATUS_TONES[ticket.status] ?? 'bg-cream-dark text-muted',
                                )}
                            >
                                {ticket.status_label}
                            </span>
                        </Link>
                    ))}
                </div>

                {selected ? (
                    <div className="rounded-card border border-line bg-white p-5">
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <h2 className="text-2xl">{selected.subject}</h2>
                            {selected.status === 'resolved' ? (
                                <button
                                    type="button"
                                    className="cursor-pointer rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold text-brand-900"
                                    onClick={() => router.patch(ticketStatus.url(selected.id), { status: 'open' })}
                                >
                                    Reopen
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    className="cursor-pointer rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold text-muted"
                                    onClick={() => router.patch(ticketStatus.url(selected.id), { status: 'resolved' })}
                                >
                                    Mark resolved
                                </button>
                            )}
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
                                        <span>{message.is_staff ? 'BookTrips team' : message.author}</span>
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
                                placeholder="Add a message…"
                                value={reply.data.body}
                                onChange={(event) => reply.setData('body', event.target.value)}
                            />
                            {Object.values(reply.errors)[0] ? (
                                <Alert tone="error">{Object.values(reply.errors)[0]}</Alert>
                            ) : null}
                            <Button type="submit" disabled={reply.processing}>
                                {reply.processing ? 'Sending…' : 'Send message'}
                            </Button>
                        </form>
                    </div>
                ) : (
                    <div className="rounded-card border border-line bg-white p-6 text-sm text-muted">
                        Pick a request on the left, or start a new one.
                    </div>
                )}
            </div>
        </div>
    );
};

Support.layout = withAppLayout;

export default Support;
