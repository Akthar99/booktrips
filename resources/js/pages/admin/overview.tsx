import { Link } from '@inertiajs/react';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import { lkr } from '@/lib/booktrips';
import { cn } from '@/lib/utils';
import type { InertiaComponent } from '@/types/inertia';

type OverviewProps = {
    stats: {
        users: number;
        travellers: number;
        partners: number;
        partners_pending: number;
        packages_live: number;
        bookings: number;
        bookings_requested: number;
        bookings_confirmed: number;
        bookings_completed: number;
        gmv_lkr: number;
        commission_due_lkr: number;
        receipts_pending: number;
        bookings_escalated: number;
    };
    finance: {
        billed_lkr: number;
        collected_lkr: number;
        outstanding_lkr: number;
        overdue_lkr: number;
        overdue_invoices: number;
        paid_invoices: number;
    };
    thisMonth: {
        label: string;
        bookings: number;
        gmv_lkr: number;
        commission_lkr: number;
        new_users: number;
        new_partners: number;
    };
    attention: {
        disputes_ready: number;
        disputes_awaiting: number;
        tickets_waiting: number;
        receipts_pending: number;
        partners_pending: number;
        escalated: number;
    };
    months: Array<{ label: string; bookings: number; gmv_lkr: number; commission_lkr: number }>;
    topPartners: Array<{
        id: number;
        name: string;
        city: string;
        completed_count: number;
        completed_gmv_lkr: number;
        invoiced_lkr: number;
        strikes: number;
    }>;
};

const AdminOverview: InertiaComponent<OverviewProps> = ({
    stats,
    finance,
    thisMonth,
    attention,
    months,
    topPartners,
}) => {
    const cards: Array<[string, string | number, string | null]> = [
        ['Travellers', stats.travellers, null],
        ['Partners', stats.partners, stats.partners_pending ? `${stats.partners_pending} pending` : null],
        ['Live listings', stats.packages_live, null],
        [
            'Bookings',
            stats.bookings,
            `${stats.bookings_requested} to confirm${stats.bookings_escalated ? ` · ${stats.bookings_escalated} over 24h` : ''}`,
        ],
        ['Confirmed GMV', lkr(stats.gmv_lkr), null],
        [
            'Commission due',
            lkr(stats.commission_due_lkr),
            stats.receipts_pending ? `${stats.receipts_pending} receipts` : null,
        ],
    ];

    const attentionItems: Array<[string, number, string]> = [
        ['Reports ready for a verdict', attention.disputes_ready, '/admin/disputes'],
        ['Reports waiting on a reply', attention.disputes_awaiting, '/admin/disputes?status=open'],
        ['Support requests to answer', attention.tickets_waiting, '/admin/support?status=awaiting_admin'],
        ['Receipts to review', attention.receipts_pending, '/admin/payments'],
        ['Partner applications', attention.partners_pending, '/admin/partners'],
        ['Escalated bookings', attention.escalated, '/admin/bookings?q='],
    ];

    const maxGmv = Math.max(...months.map((month) => month.gmv_lkr), 1);

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <p className="text-muted">Operations console — users, properties, reservations, money.</p>
            <Tabs items={ADMIN_TABS} />
            <h2 className="mb-3 text-2xl">Overview</h2>
            <p className="mb-4 text-muted">
                What needs attention across BookTrips — the same ops view a booking platform uses.
            </p>
            <div className="my-4.5 mb-7 grid grid-cols-2 gap-3 md:grid-cols-3">
                {cards.map(([label, value, extra]) => (
                    <div key={label} className="rounded-2xl border border-line bg-white p-4.5">
                        <span className="text-[13px] font-bold text-muted">{label}</span>
                        <strong className="block font-display text-[28px]">{value}</strong>
                        {extra ? <div className="mt-1 text-[13px] text-muted">{extra}</div> : null}
                    </div>
                ))}
            </div>

            <div className="mb-8 grid gap-5 lg:grid-cols-[1fr_360px]">
                <section className="rounded-2xl border border-line bg-white p-5">
                    <h3 className="mb-1 text-xl">Needs attention</h3>
                    <p className="mb-3 text-[13px] text-muted">
                        Everything below is waiting on the BookTrips team right now.
                    </p>
                    <div className="grid gap-2 sm:grid-cols-2">
                        {attentionItems.map(([label, count, href]) => (
                            <Link
                                key={label}
                                href={href}
                                className={cn(
                                    'flex items-center justify-between gap-3 rounded-xl border px-3.5 py-3 transition',
                                    count > 0
                                        ? 'border-orange-200 bg-orange-50 hover:border-warn'
                                        : 'border-line bg-white',
                                )}
                            >
                                <span className="text-[13px] font-semibold">{label}</span>
                                <strong className={cn('text-lg', count > 0 ? 'text-warn' : 'text-muted')}>
                                    {count}
                                </strong>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="rounded-2xl border border-line bg-white p-5">
                    <h3 className="mb-1 text-xl">{thisMonth.label}</h3>
                    <p className="mb-3 text-[13px] text-muted">This month so far.</p>
                    <dl className="grid grid-cols-2 gap-3 text-sm">
                        {[
                            ['Bookings', thisMonth.bookings],
                            ['GMV', lkr(thisMonth.gmv_lkr)],
                            ['Commission billed', lkr(thisMonth.commission_lkr)],
                            ['New travellers', thisMonth.new_users],
                        ].map(([label, value]) => (
                            <div key={label as string}>
                                <dt className="text-[11px] font-bold tracking-wide text-muted uppercase">
                                    {label}
                                </dt>
                                <dd className="font-bold text-brand-900">{value}</dd>
                            </div>
                        ))}
                    </dl>
                </section>
            </div>

            <div className="mb-8 grid gap-5 lg:grid-cols-2">
                <section className="rounded-2xl border border-line bg-white p-5">
                    <h3 className="mb-1 text-xl">Money</h3>
                    <p className="mb-3 text-[13px] text-muted">
                        Commission invoiced against what actually landed.
                    </p>
                    <dl className="grid grid-cols-2 gap-3 text-sm">
                        {[
                            ['Billed', lkr(finance.billed_lkr)],
                            ['Collected', lkr(finance.collected_lkr)],
                            ['Outstanding', lkr(finance.outstanding_lkr)],
                            [
                                'Overdue',
                                `${lkr(finance.overdue_lkr)} (${finance.overdue_invoices} invoices)`,
                            ],
                        ].map(([label, value]) => (
                            <div key={label as string}>
                                <dt className="text-[11px] font-bold tracking-wide text-muted uppercase">
                                    {label}
                                </dt>
                                <dd className="font-bold text-brand-900">{value}</dd>
                            </div>
                        ))}
                    </dl>
                    <Link className="mt-3 inline-block text-[13px] font-bold text-brand-800" href="/admin/payments">
                        Open finance →
                    </Link>
                </section>

                <section className="rounded-2xl border border-line bg-white p-5">
                    <h3 className="mb-1 text-xl">Last six months</h3>
                    <p className="mb-3 text-[13px] text-muted">Bookings created and GMV confirmed.</p>
                    <div className="flex h-32 items-end gap-2">
                        {months.map((month) => (
                            <div key={month.label} className="flex flex-1 flex-col items-center gap-1">
                                <div
                                    className="w-full rounded-t-md bg-brand-700/80"
                                    style={{ height: `${Math.max(6, (month.gmv_lkr / maxGmv) * 100)}%` }}
                                    title={`${month.bookings} bookings · ${lkr(month.gmv_lkr)}`}
                                />
                                <span className="text-[11px] font-bold text-muted">{month.label}</span>
                            </div>
                        ))}
                    </div>
                    <div className="mt-2 flex justify-between text-[12px] text-muted">
                        <span>{months.reduce((total, month) => total + month.bookings, 0)} bookings</span>
                        <span>
                            {lkr(months.reduce((total, month) => total + month.commission_lkr, 0))} commission
                            billed
                        </span>
                    </div>
                </section>
            </div>

            <section className="rounded-2xl border border-line bg-white">
                <h3 className="border-b border-line px-4 py-3 text-xl">Top partners by completed business</h3>
                {topPartners.length === 0 ? (
                    <p className="px-4 py-4 text-sm text-muted">No completed bookings yet.</p>
                ) : (
                    <table className="w-full border-collapse text-left">
                        <thead>
                            <tr className="text-[11px] tracking-wide text-muted uppercase">
                                <th className="px-4 py-2.5 font-bold">Business</th>
                                <th className="px-4 py-2.5 font-bold">Completed</th>
                                <th className="px-4 py-2.5 font-bold">GMV</th>
                                <th className="px-4 py-2.5 font-bold">Invoiced</th>
                                <th className="px-4 py-2.5 font-bold">Strikes</th>
                                <th className="px-4 py-2.5" />
                            </tr>
                        </thead>
                        <tbody>
                            {topPartners.map((partner) => (
                                <tr key={partner.id} className="border-t border-line text-sm">
                                    <td className="px-4 py-2.5">
                                        <strong>{partner.name}</strong>
                                        <div className="text-xs text-muted">{partner.city}</div>
                                    </td>
                                    <td className="px-4 py-2.5">{partner.completed_count}</td>
                                    <td className="px-4 py-2.5">{lkr(partner.completed_gmv_lkr)}</td>
                                    <td className="px-4 py-2.5">{lkr(partner.invoiced_lkr)}</td>
                                    <td className="px-4 py-2.5">{partner.strikes}</td>
                                    <td className="px-4 py-2.5 text-right">
                                        <Link
                                            className="text-[13px] font-bold text-brand-800"
                                            href={`/admin/businesses/${partner.id}`}
                                        >
                                            History
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </section>
        </div>
    );
};

AdminOverview.layout = withAppLayout;

export default AdminOverview;
