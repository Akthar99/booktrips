import { Link } from '@inertiajs/react';
import StatusBadge from '@/components/booktrips/status-badge';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import { lkr } from '@/lib/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type TravellerHistoryProps = {
    traveller: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        role: string;
        active: boolean;
        strikes: number;
        email_verified: boolean;
        pending_email: string | null;
        created_at: string | null;
    };
    totals: {
        bookings: number;
        completed: number;
        cancelled: number;
        spend_lkr: number;
        reviews: number;
    };
    bookings: Array<{
        id: number;
        booking_code: string;
        status: string;
        check_in: string;
        check_out: string;
        guests: number;
        total_lkr: number;
        created_at: string | null;
        package: { id: number; title: string; location?: string } | null;
        host: { id: number; name: string } | null;
    }>;
    disputes: Array<{
        id: number;
        type_label: string;
        status: string;
        summary: string;
        resolution_label: string | null;
        booking_code: string | null;
        booking_id: number | null;
        raised_by_me: boolean;
        business_name: string | null;
        created_at: string | null;
    }>;
    tickets: Array<{
        id: number;
        subject: string;
        status_label: string;
        status: string;
        last_message_at: string | null;
    }>;
};

const AdminTravellerHistory: InertiaComponent<TravellerHistoryProps> = ({
    traveller,
    totals,
    bookings,
    disputes,
    tickets,
}) => {
    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <p className="text-muted mb-2 text-[13px]">
                <Link href="/admin/users">Users</Link> · {traveller.name}
            </p>
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-3xl">{traveller.name}</h2>
                    <p className="text-muted">
                        {traveller.email}
                        {traveller.phone ? ` · ${traveller.phone}` : ''} ·
                        joined{' '}
                        {traveller.created_at
                            ? new Date(traveller.created_at).toLocaleDateString(
                                  'en-GB',
                              )
                            : '—'}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1">
                    <StatusBadge
                        status={traveller.active ? 'active' : 'suspended'}
                    />
                    {traveller.strikes > 0 ? (
                        <span className="text-warn rounded-full bg-orange-50 px-2.5 py-1 text-[12px] font-bold">
                            {traveller.strikes} strike
                            {traveller.strikes === 1 ? '' : 's'}
                        </span>
                    ) : null}
                    {!traveller.email_verified ? (
                        <span className="text-warn rounded-full bg-orange-50 px-2.5 py-1 text-[12px] font-bold">
                            email unverified
                        </span>
                    ) : null}
                </div>
            </div>

            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-5">
                {[
                    ['Bookings', totals.bookings],
                    ['Completed', totals.completed],
                    ['Cancelled', totals.cancelled],
                    ['Value booked', lkr(totals.spend_lkr)],
                    ['Reviews', totals.reviews],
                ].map(([label, value]) => (
                    <div
                        key={label as string}
                        className="border-line rounded-2xl border bg-white p-4"
                    >
                        <span className="text-muted text-[12px] font-bold">
                            {label}
                        </span>
                        <strong className="font-display block text-[22px]">
                            {value}
                        </strong>
                    </div>
                ))}
            </div>

            <section className="border-line mb-6 overflow-x-auto rounded-2xl border bg-white">
                <h3 className="border-line border-b px-4 py-3 text-xl">
                    Booking history
                </h3>
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="text-muted text-[11px] tracking-wide uppercase">
                            <th className="px-4 py-2.5 font-bold">Code</th>
                            <th className="px-4 py-2.5 font-bold">Package</th>
                            <th className="px-4 py-2.5 font-bold">Business</th>
                            <th className="px-4 py-2.5 font-bold">Dates</th>
                            <th className="px-4 py-2.5 font-bold">Status</th>
                            <th className="px-4 py-2.5 font-bold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {bookings.map((booking) => (
                            <tr
                                key={booking.id}
                                className="border-line border-t text-sm"
                            >
                                <td className="text-brand-900 px-4 py-2.5 font-bold">
                                    {booking.booking_code}
                                </td>
                                <td className="px-4 py-2.5">
                                    {booking.package?.title}
                                </td>
                                <td className="px-4 py-2.5">
                                    {booking.host ? (
                                        <Link
                                            className="text-brand-800 font-bold"
                                            href={`/admin/businesses/${booking.host.id}`}
                                        >
                                            {booking.host.name}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </td>
                                <td className="px-4 py-2.5 text-xs">
                                    {booking.check_in} → {booking.check_out}
                                </td>
                                <td className="px-4 py-2.5">
                                    <StatusBadge status={booking.status} />
                                </td>
                                <td className="px-4 py-2.5">
                                    {lkr(booking.total_lkr)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {bookings.length === 0 ? (
                    <p className="text-muted px-4 py-4 text-sm">
                        No bookings yet.
                    </p>
                ) : null}
            </section>

            <div className="grid gap-4 lg:grid-cols-2">
                <section className="border-line rounded-2xl border bg-white p-5">
                    <h3 className="mb-2 text-xl">Reports</h3>
                    {disputes.length === 0 ? (
                        <p className="text-muted text-[13px]">
                            No reports involving this traveller.
                        </p>
                    ) : (
                        disputes.map((dispute) => (
                            <div
                                key={dispute.id}
                                className="border-line mb-2 rounded-xl border px-3 py-2.5"
                            >
                                <div className="flex items-center justify-between gap-2 text-[13px]">
                                    <strong>{dispute.type_label}</strong>
                                    <span className="text-muted">
                                        {dispute.raised_by_me
                                            ? 'raised by them'
                                            : 'against them'}
                                    </span>
                                </div>
                                <p className="text-[13px]">{dispute.summary}</p>
                                <p className="text-muted text-[12px]">
                                    {dispute.business_name} ·{' '}
                                    {dispute.booking_code}
                                    {dispute.resolution_label
                                        ? ` · ${dispute.resolution_label}`
                                        : ''}
                                </p>
                            </div>
                        ))
                    )}
                </section>

                <section className="border-line rounded-2xl border bg-white p-5">
                    <h3 className="mb-2 text-xl">Support threads</h3>
                    {tickets.length === 0 ? (
                        <p className="text-muted text-[13px]">
                            No support requests from this account.
                        </p>
                    ) : (
                        tickets.map((ticket) => (
                            <Link
                                key={ticket.id}
                                href={`/admin/support?ticket=${ticket.id}`}
                                className="border-line hover:bg-cream mb-2 block rounded-xl border px-3 py-2 text-[13px]"
                            >
                                <strong className="block">
                                    {ticket.subject}
                                </strong>
                                <span className="text-muted">
                                    {ticket.status_label}
                                </span>
                            </Link>
                        ))
                    )}
                </section>
            </div>
        </div>
    );
};

AdminTravellerHistory.layout = withAppLayout;

export default AdminTravellerHistory;
