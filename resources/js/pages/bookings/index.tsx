import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { cancel } from '@/actions/App/Http/Controllers/BookingController';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import Pagination from '@/components/booktrips/pagination';
import StatusBadge from '@/components/booktrips/status-badge';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { BookingData, Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type BookingsProps = { bookings: Paginated<BookingData> };

const Bookings: InertiaComponent<BookingsProps> = ({ bookings }) => {
    const [cancelId, setCancelId] = useState<number | null>(null);
    const [busy, setBusy] = useState(false);

    function confirmCancel(bookingId: number) {
        setBusy(true);
        router.post(
            cancel.url(bookingId),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusy(false);
                    setCancelId(null);
                },
            },
        );
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">My trips</h1>
            <p className="mb-5 text-muted">Show the booking code to your host. Pay there.</p>
            {bookings.data.length === 0 ? (
                <p>
                    No bookings yet.{' '}
                    <Link className="font-bold text-brand-800" href="/search">
                        Find a package
                    </Link>
                </p>
            ) : null}
            <div className="grid gap-3">
                {bookings.data.map((booking) => (
                    <div
                        key={booking.id}
                        className="grid grid-cols-1 items-center gap-3.5 rounded-[14px] border border-line bg-white px-4 py-3.5 sm:grid-cols-[120px_1fr_auto]"
                    >
                        {booking.package?.images?.[0] ? (
                            <img
                                src={booking.package.images[0]}
                                alt=""
                                className="h-[88px] w-[120px] rounded-[10px] object-cover"
                            />
                        ) : (
                            <div />
                        )}
                        <div>
                            <Link href={`/account/bookings/${booking.id}`}>
                                <strong>{booking.package?.title}</strong>
                            </Link>
                            <div className="text-[13px] text-muted">
                                {booking.check_in} → {booking.check_out} · {booking.guests} guests
                            </div>
                            <div className="mt-1.5 flex items-center gap-2">
                                <StatusBadge status={booking.status} />
                                <span className="text-[13px] text-muted">{booking.booking_code}</span>
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="font-extrabold text-brand-900">{lkr(booking.total_lkr)}</div>
                            {booking.status === 'confirmed' || booking.status === 'requested' ? (
                                <button
                                    type="button"
                                    className="mt-2 cursor-pointer rounded-full border border-red-200 bg-white px-3 py-1.5 text-[13px] font-bold text-danger transition hover:border-red-400"
                                    onClick={() => setCancelId(booking.id)}
                                >
                                    Cancel
                                </button>
                            ) : null}
                        </div>
                    </div>
                ))}
            </div>
            <Pagination page={bookings.current_page} lastPage={bookings.last_page} total={bookings.total} />

            <ConfirmDialog
                open={cancelId !== null}
                title="Cancel this reservation?"
                message="This reservation will be cancelled and the host will no longer expect you."
                confirmLabel="Cancel reservation"
                danger
                busy={busy}
                onConfirm={() => cancelId !== null && confirmCancel(cancelId)}
                onCancel={() => setCancelId(null)}
            />
        </div>
    );
};

Bookings.layout = withAppLayout;

export default Bookings;
