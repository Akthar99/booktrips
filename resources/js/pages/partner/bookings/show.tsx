import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { updateStatus as partnerBookingStatus } from '@/actions/App/Http/Controllers/Partner/PartnerBookingController';
import Alert from '@/components/booktrips/alert';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import DisputePanel, {
    type DisputeData,
} from '@/components/booktrips/dispute-panel';
import StatusBadge from '@/components/booktrips/status-badge';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { BookingData } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type PartnerBookingProps = {
    booking: BookingData;
    disputes: DisputeData[];
    reportTypes: Array<{ value: string; label: string; blurb: string }>;
    canReport: boolean;
};

const ACTION_COPY = {
    confirmed: {
        title: 'Confirm booking?',
        message:
            'The guest will be notified and their contact details will become visible.',
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
} as const;

type PendingAction = keyof typeof ACTION_COPY;

const PartnerBooking: InertiaComponent<PartnerBookingProps> = ({
    booking,
    disputes,
    reportTypes,
    canReport,
}) => {
    const [pending, setPending] = useState<PendingAction | null>(null);
    const [busy, setBusy] = useState(false);
    const open =
        booking.status === 'confirmed' || booking.status === 'completed';
    const copy = pending ? ACTION_COPY[pending] : null;

    function apply(status: PendingAction) {
        setBusy(true);
        router.patch(
            partnerBookingStatus.url(booking.id),
            { status },
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
        <div className="mx-auto w-[min(640px,calc(100%-2rem))] py-7 pb-14">
            <p className="text-muted text-[13px]">
                <Link href="/partners/bookings">Reservations</Link> ·{' '}
                {booking.booking_code}
            </p>
            <h1 className="mt-2 text-4xl">Guest arrival</h1>
            <p className="text-muted">{booking.package?.title}</p>
            <div className="border-line mt-4 rounded-[14px] border bg-white px-4 py-3.5">
                <p>
                    <strong>When</strong> {booking.check_in} →{' '}
                    {booking.check_out}
                </p>
                <p>
                    <strong>Guests</strong> {booking.guests}
                </p>
                <p>
                    <strong>Lead name</strong> {booking.guest_name}
                </p>
                {open ? (
                    <>
                        <p>
                            <strong>Phone</strong> {booking.guest_phone || '—'}
                        </p>
                        <p>
                            <strong>Email</strong> {booking.guest_email || '—'}
                        </p>
                    </>
                ) : (
                    <Alert tone="warn">
                        Phone and email stay hidden until you confirm this
                        request.
                    </Alert>
                )}
                {booking.notes ? (
                    <p>
                        <strong>Notes</strong> {booking.notes}
                    </p>
                ) : null}
                <p className="mt-2 flex items-center gap-2">
                    <StatusBadge status={booking.status} /> ·{' '}
                    {lkr(booking.total_lkr)} at destination
                </p>
            </div>
            {booking.status === 'requested' ? (
                <div className="mt-4 flex gap-2.5">
                    <button
                        type="button"
                        className="bg-brand-800 hover:bg-brand-900 cursor-pointer rounded-full px-4.5 py-2.5 text-sm font-bold text-white transition"
                        onClick={() => setPending('confirmed')}
                    >
                        Confirm
                    </button>
                    <button
                        type="button"
                        className="text-danger cursor-pointer rounded-full border border-red-200 bg-white px-4.5 py-2.5 text-sm font-bold transition hover:border-red-400"
                        onClick={() => setPending('rejected')}
                    >
                        Reject
                    </button>
                </div>
            ) : null}
            {booking.status === 'confirmed' ? (
                <button
                    type="button"
                    className="border-line text-brand-900 hover:border-brand-700 mt-4 cursor-pointer rounded-full border bg-white px-4.5 py-2.5 text-sm font-bold transition"
                    onClick={() => setPending('completed')}
                >
                    Mark finished
                </button>
            ) : null}

            <ConfirmDialog
                open={pending !== null}
                title={copy?.title ?? ''}
                message={copy?.message ?? ''}
                confirmLabel={copy?.label ?? 'Confirm'}
                danger={copy?.danger ?? false}
                busy={busy}
                onConfirm={() => pending && apply(pending)}
                onCancel={() => setPending(null)}
            />

            <DisputePanel
                bookingId={booking.id}
                disputes={disputes}
                reportTypes={reportTypes}
                canReport={canReport}
            />
        </div>
    );
};

PartnerBooking.layout = withAppLayout;

export default PartnerBooking;
