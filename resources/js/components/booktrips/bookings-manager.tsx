import { useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import DateInput from '@/components/booktrips/date-input';
import StatusBadge from '@/components/booktrips/status-badge';
import { Input, Select } from '@/components/booktrips/field';
import { lkr, todayIso } from '@/lib/booktrips';
import type { BookingData, Paginated } from '@/types/booktrips';

type View = 'list' | 'day';
type PendingAction = 'confirmed' | 'rejected' | 'completed' | null;

const ACTION_COPY: Record<
    Exclude<PendingAction, null>,
    { title: string; message: string; label: string; danger: boolean }
> = {
    confirmed: {
        title: 'Confirm booking?',
        message:
            'The guest will be notified and their contact details will be visible.',
        label: 'Confirm booking',
        danger: false,
    },
    rejected: {
        title: 'Reject booking?',
        message:
            'The guest will be notified that this request cannot be accepted.',
        label: 'Reject booking',
        danger: true,
    },
    completed: {
        title: 'Mark booking finished?',
        message:
            'This adds the booking to your completed income and monthly commission calculation.',
        label: 'Mark finished',
        danger: false,
    },
};

export default function BookingsManager({
    bookings,
    filters,
    counts,
    mode,
    baseUrl,
    statusUrlFor,
}: {
    bookings: Paginated<BookingData>;
    filters: { q: string; status: string; date: string };
    counts: { requested: number; confirmed: number };
    mode: 'partner' | 'admin';
    baseUrl: string;
    statusUrlFor: (bookingId: number) => string;
}) {
    const [view, setView] = useState<View>(
        filters.date && filters.date !== todayIso() ? 'day' : 'list',
    );
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status || 'all');
    const [date, setDate] = useState(filters.date || todayIso());
    const [pending, setPending] = useState<{
        booking: BookingData;
        action: Exclude<PendingAction, null>;
    } | null>(null);
    const [busy, setBusy] = useState(false);

    const rows = bookings.data;
    const showBusiness = mode === 'admin';

    const totals = useMemo(
        () => ({
            requested: counts.requested,
            confirmed: counts.confirmed,
        }),
        [counts],
    );

    function load(
        overrides: Partial<{
            q: string;
            status: string;
            date: string;
            view: View;
        }> = {},
    ) {
        const nextQ = overrides.q ?? q;
        const nextStatus = overrides.status ?? status;
        const nextView = overrides.view ?? view;
        const nextDate = overrides.date ?? date;

        router.get(
            baseUrl,
            {
                q: nextQ || undefined,
                status: nextStatus !== 'all' ? nextStatus : undefined,
                date: nextView === 'day' ? nextDate : undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    function applyStatus(
        booking: BookingData,
        next: Exclude<PendingAction, null>,
    ) {
        setBusy(true);
        router.patch(
            statusUrlFor(booking.id),
            { status: next },
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusy(false);
                    setPending(null);
                },
            },
        );
    }

    return (
        <div>
            <div className="my-3 flex flex-wrap items-center gap-2.5">
                <div className="border-line flex overflow-hidden rounded-full border bg-white">
                    {(['list', 'day'] as View[]).map((option) => (
                        <button
                            key={option}
                            type="button"
                            className={
                                'cursor-pointer border-0 px-3.5 py-2 text-sm font-bold capitalize ' +
                                (view === option
                                    ? 'bg-brand-800 text-white'
                                    : 'text-ink bg-transparent')
                            }
                            onClick={() => {
                                setView(option);
                                load({ view: option });
                            }}
                        >
                            {option}
                        </button>
                    ))}
                </div>
                <form
                    className="flex flex-1 flex-wrap items-center gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        load();
                    }}
                >
                    <Input
                        className="min-w-50 flex-1"
                        placeholder={
                            showBusiness
                                ? 'Search code, guest, package, business'
                                : 'Search code, guest, package'
                        }
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                    />
                    <Select
                        className="w-auto"
                        value={status}
                        onChange={(event) => {
                            setStatus(event.target.value);
                            load({ status: event.target.value });
                        }}
                    >
                        <option value="all">All statuses</option>
                        <option value="requested">Requested</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </Select>
                    {view === 'day' ? (
                        <DateInput
                            className="w-45"
                            value={date}
                            onChange={(value) => {
                                setDate(value);
                                load({ date: value, view: 'day' });
                            }}
                        />
                    ) : null}
                    <button
                        type="submit"
                        className="bg-brand-800 hover:bg-brand-900 cursor-pointer rounded-full px-4 py-2 text-[13px] font-bold text-white transition"
                    >
                        Search
                    </button>
                </form>
            </div>
            <p className="text-muted mt-2 mb-3.5 text-[13px]">
                {bookings.total} shown · {totals.requested} requested ·{' '}
                {totals.confirmed} confirmed
            </p>

            {view === 'day' ? (
                <div className="grid gap-2.5">
                    {rows.length === 0 ? (
                        <p className="text-muted">No bookings on {date}.</p>
                    ) : null}
                    {rows.map((booking) => (
                        <div
                            key={booking.id}
                            className="border-line rounded-[14px] border bg-white px-4 py-3.5"
                        >
                            <strong>
                                {booking.booking_code} ·{' '}
                                {booking.package?.title}
                            </strong>
                            <div className="text-muted text-[13px]">
                                {showBusiness && booking.host?.name ? (
                                    <>{booking.host.name} · </>
                                ) : null}
                                {booking.guest_name} · {booking.guests} guests ·{' '}
                                {lkr(booking.total_lkr)}
                            </div>
                            <div className="mt-2 flex flex-wrap items-center gap-2">
                                <StatusBadge status={booking.status} />
                                <Actions
                                    booking={booking}
                                    mode={mode}
                                    onAction={(action) =>
                                        setPending({ booking, action })
                                    }
                                />
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="border-line overflow-x-auto rounded-2xl border bg-white">
                    <table className="w-full border-collapse text-left">
                        <thead>
                            <tr className="bg-cream-dark text-muted text-[11px] tracking-wide uppercase">
                                <th className="px-3.5 py-3 font-bold">Code</th>
                                <th className="px-3.5 py-3 font-bold">
                                    Package
                                </th>
                                {showBusiness ? (
                                    <th className="px-3.5 py-3 font-bold">
                                        Business
                                    </th>
                                ) : null}
                                <th className="px-3.5 py-3 font-bold">Dates</th>
                                <th className="px-3.5 py-3 font-bold">Guest</th>
                                <th className="px-3.5 py-3 font-bold">Total</th>
                                <th className="px-3.5 py-3 font-bold">
                                    Status
                                </th>
                                <th className="px-3.5 py-3 text-right font-bold">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((booking) => (
                                <tr
                                    key={booking.id}
                                    className="border-line border-t align-middle"
                                >
                                    <td className="px-3.5 py-3 text-sm whitespace-nowrap">
                                        {booking.booking_code}
                                    </td>
                                    <td className="px-3.5 py-3 text-sm">
                                        {booking.package?.title}
                                    </td>
                                    {showBusiness ? (
                                        <td className="px-3.5 py-3 text-sm">
                                            {booking.host?.name || '—'}
                                            {booking.host?.email ? (
                                                <>
                                                    <br />
                                                    <span className="text-muted">
                                                        {booking.host.email}
                                                    </span>
                                                </>
                                            ) : null}
                                        </td>
                                    ) : null}
                                    <td className="px-3.5 py-3 text-sm whitespace-nowrap">
                                        {booking.check_in} → {booking.check_out}
                                    </td>
                                    <td className="px-3.5 py-3 text-sm">
                                        {mode === 'admin' ? (
                                            booking.guest_name
                                        ) : (
                                            <Link
                                                className="text-brand-800 font-bold"
                                                href={`/partners/bookings/${booking.id}`}
                                            >
                                                {booking.guest_name}
                                            </Link>
                                        )}
                                        <br />
                                        <span className="text-muted">
                                            {booking.contact_hidden
                                                ? 'Contact after confirm'
                                                : booking.guest_phone || '—'}
                                        </span>
                                    </td>
                                    <td className="px-3.5 py-3 text-sm whitespace-nowrap">
                                        {lkr(booking.total_lkr)}
                                    </td>
                                    <td className="px-3.5 py-3">
                                        <StatusBadge status={booking.status} />
                                    </td>
                                    <td className="px-3.5 py-3 text-right">
                                        <Actions
                                            booking={booking}
                                            mode={mode}
                                            onAction={(action) =>
                                                setPending({ booking, action })
                                            }
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <ConfirmDialog
                open={Boolean(pending)}
                title={pending ? ACTION_COPY[pending.action].title : ''}
                message={pending ? ACTION_COPY[pending.action].message : ''}
                confirmLabel={
                    pending ? ACTION_COPY[pending.action].label : 'Confirm'
                }
                danger={pending ? ACTION_COPY[pending.action].danger : false}
                busy={busy}
                onConfirm={() =>
                    pending && applyStatus(pending.booking, pending.action)
                }
                onCancel={() => setPending(null)}
            />
        </div>
    );
}

function Actions({
    booking,
    onAction,
}: {
    booking: BookingData;
    mode: 'partner' | 'admin';
    onAction: (action: Exclude<PendingAction, null>) => void;
}) {
    if (booking.status === 'requested') {
        return (
            <span className="inline-flex items-center gap-3.5 whitespace-nowrap">
                <button
                    type="button"
                    className="text-brand-800 cursor-pointer border-0 bg-transparent p-0 text-[13px] font-bold hover:underline"
                    onClick={() => onAction('confirmed')}
                >
                    Confirm
                </button>
                <button
                    type="button"
                    className="text-danger cursor-pointer border-0 bg-transparent p-0 text-[13px] font-bold hover:underline"
                    onClick={() => onAction('rejected')}
                >
                    Reject
                </button>
            </span>
        );
    }

    if (booking.status === 'confirmed') {
        return (
            <button
                type="button"
                className="text-brand-800 cursor-pointer border-0 bg-transparent p-0 text-[13px] font-bold hover:underline"
                onClick={() => onAction('completed')}
            >
                Mark finished
            </button>
        );
    }

    return <span className="text-muted">—</span>;
}
