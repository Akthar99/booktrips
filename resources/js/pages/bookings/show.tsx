import { Link, useForm } from '@inertiajs/react';
import Alert from '@/components/booktrips/alert';
import DisputePanel, { type DisputeData } from '@/components/booktrips/dispute-panel';
import { Field, Input, Textarea } from '@/components/booktrips/field';
import StarRating from '@/components/booktrips/star-rating';
import StatusBadge from '@/components/booktrips/status-badge';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { BookingData, SharedProps } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type BookingShowProps = {
    booking: BookingData;
    disputes: DisputeData[];
    reportTypes: Array<{ value: string; label: string; blurb: string }>;
    canReport: boolean;
};

const BookingShow: InertiaComponent<BookingShowProps> = ({ booking, disputes, reportTypes, canReport }) => {
    const reviewForm = useForm({
        booking_id: booking.id,
        rating: 5,
        title: '',
        comment: '',
    });

    function submitReview(event: React.FormEvent) {
        event.preventDefault();
        reviewForm.post('/reviews', { preserveScroll: true });
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-12">
            <div className="mx-auto max-w-[640px] rounded-[20px] border border-line bg-white p-8 text-center text-left">
                <h1 className="mb-3 text-center text-3xl">Booking {booking.booking_code}</h1>
                <div className="mb-3 text-center text-[28px] font-extrabold tracking-[0.08em] text-brand-800">
                    {booking.booking_code}
                </div>
                <p className="text-center">{booking.package?.title}</p>
                <p className="text-center text-muted">
                    {booking.package?.location} · {booking.check_in} → {booking.check_out} · {booking.guests} guests
                </p>
                <p className="mt-3 text-center">
                    <strong>{lkr(booking.total_lkr)}</strong> due on arrival
                </p>
                <p className="my-2 mb-5 text-center">
                    <StatusBadge status={booking.status} />
                    {booking.escalated && booking.status === 'requested' ? (
                        <span className="ml-2 text-[13px] text-warn">Waiting for the host for over 24h</span>
                    ) : null}
                </p>
                {booking.host ? (
                    <p className="text-center text-muted">
                        Host: {booking.host.name}
                        {booking.host.phone ? ` · ${booking.host.phone}` : ''}
                    </p>
                ) : null}
                {booking.notes ? <p className="mt-3 text-center text-[13px] text-muted">Your note: {booking.notes}</p> : null}

                {booking.status === 'completed' ? (
                    <div className="mx-auto mt-5 rounded-[14px] border border-brand-100 bg-brand-50 p-4.5">
                        {booking.review ? (
                            <>
                                <h3 className="mb-1 font-sans text-base font-bold">Your review</h3>
                                <p className="flex items-center gap-2">
                                    <StarRating value={booking.review.rating} readOnly />
                                    {booking.review.title ? ` ${booking.review.title}` : ''}
                                </p>
                                <p className="text-[13px] text-muted">{booking.review.comment}</p>
                            </>
                        ) : (
                            <form onSubmit={submitReview}>
                                <h3 className="mb-1 font-sans text-base font-bold">Review this trip</h3>
                                <p className="my-1.5 mb-3 text-[13px] text-muted">
                                    Your rating helps other travellers choose well.
                                </p>
                                {Object.values(reviewForm.errors)[0] ? (
                                    <Alert tone="error">{Object.values(reviewForm.errors)[0]}</Alert>
                                ) : null}
                                <div className="mb-3 flex flex-col gap-1.5">
                                    <span className="text-xs font-bold tracking-wide text-muted uppercase">Rating</span>
                                    <StarRating
                                        value={reviewForm.data.rating}
                                        onChange={(rating) => reviewForm.setData('rating', rating)}
                                    />
                                </div>
                                <Field label="Title (optional)">
                                    <Input
                                        value={reviewForm.data.title}
                                        onChange={(event) => reviewForm.setData('title', event.target.value)}
                                    />
                                </Field>
                                <Field label="Your review">
                                    <Textarea
                                        required
                                        maxLength={1200}
                                        placeholder="What should another traveller know?"
                                        value={reviewForm.data.comment}
                                        onChange={(event) => reviewForm.setData('comment', event.target.value)}
                                    />
                                </Field>
                                <button
                                    type="submit"
                                    className="cursor-pointer rounded-full bg-brand-800 px-3.5 py-2 text-[13px] font-bold text-white transition hover:bg-brand-900 disabled:opacity-55"
                                    disabled={reviewForm.processing}
                                >
                                    {reviewForm.processing ? 'Publishing…' : 'Publish review'}
                                </button>
                            </form>
                        )}
                    </div>
                ) : null}

                <div className="mt-5 flex justify-center gap-2">
                    <Link
                        href="/account/bookings"
                        className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                    >
                        All trips
                    </Link>
                    <Link
                        href={`/packages/${booking.package?.slug ?? booking.package?.id ?? ''}`}
                        className="inline-flex rounded-full border border-line bg-white px-4.5 py-2.5 text-sm font-bold text-brand-900 transition hover:border-brand-700"
                    >
                        View package
                    </Link>
                </div>

                <DisputePanel
                    bookingId={booking.id}
                    disputes={disputes}
                    reportTypes={reportTypes}
                    canReport={canReport}
                />
            </div>
        </div>
    );
};

BookingShow.layout = withAppLayout;

export default BookingShow;