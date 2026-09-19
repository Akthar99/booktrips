import { Link } from '@inertiajs/react';
import StatusBadge from '@/components/booktrips/status-badge';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import { lkr } from '@/lib/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type BusinessHistoryProps = {
    business: {
        id: number;
        name: string;
        type: string;
        city: string;
        district: string | null;
        phone: string | null;
        email: string | null;
        website: string | null;
        instagram: string | null;
        facebook: string | null;
        tiktok: string | null;
        whatsapp: string | null;
        approved: boolean;
        strikes: number;
        phone_verified_at: string | null;
        created_at: string | null;
        owner: {
            id: number;
            name: string;
            email: string;
            active: boolean;
            email_verified: boolean;
        } | null;
        packages_total: number;
        packages_live: number;
    };
    totals: {
        bookings: number;
        completed: number;
        gmv_lkr: number;
        commission_lkr: number;
        paid_lkr: number;
    };
    bookings: Array<{
        id: number;
        booking_code: string;
        status: string;
        check_in: string;
        check_out: string;
        guests: number;
        guest_name: string;
        guest_email: string;
        total_lkr: number;
        commission_lkr: number;
        created_at: string | null;
        package: { id: number; title: string } | null;
    }>;
    invoices: Array<{
        id: number;
        period: string;
        period_label: string;
        amount_lkr: number;
        status: string;
        due_date: string;
        paid_at: string | null;
        lines: Array<Record<string, unknown>>;
    }>;
    disputes: Array<{
        id: number;
        type_label: string;
        status: string;
        summary: string;
        resolution_label: string | null;
        booking_code: string | null;
        booking_id: number | null;
        against: string | null;
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

const AdminBusinessHistory: InertiaComponent<BusinessHistoryProps> = ({
    business,
    totals,
    bookings,
    invoices,
    disputes,
    tickets,
}) => {
    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <p className="text-muted mb-2 text-[13px]">
                <Link href="/admin/partners">Partners</Link> · {business.name}
            </p>
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-3xl">{business.name}</h2>
                    <p className="text-muted">
                        {business.type} · {business.city}
                        {business.district ? `, ${business.district}` : ''} ·
                        joined{' '}
                        {business.created_at
                            ? new Date(business.created_at).toLocaleDateString(
                                  'en-GB',
                              )
                            : '—'}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1">
                    <StatusBadge
                        status={business.approved ? 'approved' : 'pending'}
                    />
                    {business.strikes > 0 ? (
                        <span className="text-warn rounded-full bg-orange-50 px-2.5 py-1 text-[12px] font-bold">
                            {business.strikes} strike
                            {business.strikes === 1 ? '' : 's'}
                        </span>
                    ) : null}
                </div>
            </div>

            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-5">
                {[
                    ['Bookings', totals.bookings],
                    ['Completed', totals.completed],
                    ['GMV', lkr(totals.gmv_lkr)],
                    ['Commission billed', lkr(totals.commission_lkr)],
                    ['Commission paid', lkr(totals.paid_lkr)],
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

            <div className="mb-6 grid gap-4 lg:grid-cols-[1fr_320px]">
                <section className="border-line rounded-2xl border bg-white p-5">
                    <h3 className="mb-2 text-xl">Profile</h3>
                    <dl className="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt className="text-muted text-[11px] font-bold tracking-wide uppercase">
                                Owner
                            </dt>
                            <dd>
                                {business.owner?.name ?? '—'}
                                {business.owner ? (
                                    <Link
                                        className="text-brand-800 ml-2 text-[12px] font-bold"
                                        href={`/admin/travellers/${business.owner.id}`}
                                    >
                                        history
                                    </Link>
                                ) : null}
                                <br />
                                <span className="text-muted">
                                    {business.owner?.email}
                                </span>
                                <span className="text-muted block text-[12px]">
                                    {business.owner?.active
                                        ? 'Account active'
                                        : 'Account suspended'}{' '}
                                    ·{' '}
                                    {business.owner?.email_verified
                                        ? 'email verified'
                                        : 'email unverified'}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted text-[11px] font-bold tracking-wide uppercase">
                                Contact
                            </dt>
                            <dd>
                                {business.phone ?? '—'}
                                {business.phone_verified_at ? (
                                    <span className="text-brand-800 block text-[12px] font-bold">
                                        SMS verified{' '}
                                        {new Date(
                                            business.phone_verified_at,
                                        ).toLocaleDateString('en-GB')}
                                    </span>
                                ) : (
                                    <span className="text-warn block text-[12px] font-bold">
                                        Phone not verified
                                    </span>
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted text-[11px] font-bold tracking-wide uppercase">
                                Listings
                            </dt>
                            <dd>
                                {business.packages_live} live of{' '}
                                {business.packages_total}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted text-[11px] font-bold tracking-wide uppercase">
                                Links
                            </dt>
                            <dd className="flex flex-wrap gap-2">
                                {[
                                    ['Website', business.website],
                                    ['Instagram', business.instagram],
                                    ['Facebook', business.facebook],
                                    ['TikTok', business.tiktok],
                                    ['WhatsApp', business.whatsapp],
                                ].map(([label, value]) =>
                                    value ? (
                                        <a
                                            key={label as string}
                                            href={String(value)}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-brand-800 font-bold"
                                        >
                                            {label}
                                        </a>
                                    ) : null,
                                )}
                            </dd>
                        </div>
                    </dl>
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

            <section className="border-line mb-6 overflow-x-auto rounded-2xl border bg-white">
                <h3 className="border-line border-b px-4 py-3 text-xl">
                    Booking history
                </h3>
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="text-muted text-[11px] tracking-wide uppercase">
                            <th className="px-4 py-2.5 font-bold">Code</th>
                            <th className="px-4 py-2.5 font-bold">Traveller</th>
                            <th className="px-4 py-2.5 font-bold">Package</th>
                            <th className="px-4 py-2.5 font-bold">Dates</th>
                            <th className="px-4 py-2.5 font-bold">Status</th>
                            <th className="px-4 py-2.5 font-bold">Total</th>
                            <th className="px-4 py-2.5 font-bold">
                                Commission
                            </th>
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
                                    {booking.guest_name}
                                    <div className="text-muted text-xs">
                                        {booking.guest_email}
                                    </div>
                                </td>
                                <td className="px-4 py-2.5">
                                    {booking.package?.title}
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
                                <td className="px-4 py-2.5">
                                    {booking.commission_lkr > 0
                                        ? lkr(booking.commission_lkr)
                                        : '—'}
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
                    <h3 className="mb-2 text-xl">Commission invoices</h3>
                    {invoices.length === 0 ? (
                        <p className="text-muted text-[13px]">
                            Nothing invoiced yet.
                        </p>
                    ) : (
                        invoices.map((invoice) => (
                            <div
                                key={invoice.id}
                                className="border-line mb-2 rounded-xl border px-3 py-2.5"
                            >
                                <div className="flex items-center justify-between gap-2 text-[13px]">
                                    <strong>{invoice.period_label}</strong>
                                    <span className="font-bold">
                                        {lkr(invoice.amount_lkr)}
                                    </span>
                                </div>
                                <div className="text-muted text-[12px]">
                                    {invoice.status} · due {invoice.due_date}
                                    {invoice.paid_at
                                        ? ` · paid ${new Date(invoice.paid_at).toLocaleDateString('en-GB')}`
                                        : ''}
                                </div>
                            </div>
                        ))
                    )}
                </section>

                <section className="border-line rounded-2xl border bg-white p-5">
                    <h3 className="mb-2 text-xl">Reports</h3>
                    {disputes.length === 0 ? (
                        <p className="text-muted text-[13px]">
                            No reports involving this business.
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
                                        {dispute.status.replace('_', ' ')}
                                    </span>
                                </div>
                                <p className="text-[13px]">{dispute.summary}</p>
                                <p className="text-muted text-[12px]">
                                    {dispute.booking_code} · against{' '}
                                    {dispute.against}
                                    {dispute.resolution_label
                                        ? ` · ${dispute.resolution_label}`
                                        : ''}
                                </p>
                            </div>
                        ))
                    )}
                </section>
            </div>
        </div>
    );
};

AdminBusinessHistory.layout = withAppLayout;

export default AdminBusinessHistory;
