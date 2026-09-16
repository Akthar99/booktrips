import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
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
};

const AdminOverview: InertiaComponent<OverviewProps> = ({ stats }) => {
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

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <p className="text-muted">Operations console — users, properties, reservations, money.</p>
            <Tabs
                items={[
                    { label: 'Overview', href: '/admin', exact: true },
                    { label: 'Users', href: '/admin/users' },
                    { label: 'Partners', href: '/admin/partners' },
                    { label: 'Listings', href: '/admin/listings' },
                    { label: 'Bookings', href: '/admin/bookings' },
                    { label: 'Finance', href: '/admin/payments' },
                    { label: 'Reviews', href: '/admin/reviews' },
                ]}
            />
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
        </div>
    );
};

AdminOverview.layout = withAppLayout;

export default AdminOverview;
