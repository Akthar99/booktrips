import { useState } from 'react';
import { router } from '@inertiajs/react';
import { index as analyticsRoute } from '@/actions/App/Http/Controllers/Partner/PartnerAnalyticsController';
import DateInput from '@/components/booktrips/date-input';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type AnalyticsData = {
    business: { id: number; name: string; city: string };
    range: { from: string | null; to: string | null };
    summary: {
        packages: number;
        live_packages: number;
        discounted_packages: number;
        bookings: number;
        requested: number;
        confirmed: number;
        completed: number;
        rejected: number;
        guests: number;
        income_lkr: number;
        potential_income_lkr: number;
        discounts_lkr: number;
        commission_lkr: number;
        net_income_lkr: number;
        average_completed_lkr: number;
        confirmation_rate: number;
    };
    monthly: Array<{
        period: string;
        bookings: number;
        completed: number;
        income_lkr: number;
        commission_lkr: number;
    }>;
    packages: Array<{
        id: number;
        title: string;
        category: string;
        active: boolean;
        price_lkr: number;
        discount_type: string;
        discount_value: number;
        bookings: number;
        requested: number;
        confirmed: number;
        completed: number;
        rejected: number;
        cancelled: number;
        guests: number;
        income_lkr: number;
        potential_income_lkr: number;
        discounts_lkr: number;
        confirmation_rate: number;
    }>;
};

type AnalyticsProps = { analytics: AnalyticsData };

const Analytics: InertiaComponent<AnalyticsProps> = ({ analytics }) => {
    const { summary, monthly, packages, range } = analytics;
    const [from, setFrom] = useState(range.from ?? '');
    const [to, setTo] = useState(range.to ?? '');

    function apply(nextFrom: string, nextTo: string) {
        router.get(
            analyticsRoute.url(),
            { from: nextFrom || undefined, to: nextTo || undefined },
            { preserveState: true, preserveScroll: true },
        );
    }

    const maxIncome = Math.max(...monthly.map((month) => month.income_lkr), 1);
    const chartMonths = [...monthly].reverse();

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Analytics</h1>
            <p className="text-muted">
                Where the money and the missed requests come from. Counts use booking creation dates.
            </p>
            <Tabs
                items={[
                    { label: 'Overview', href: '/partners/dashboard', exact: true },
                    { label: 'Reservations', href: '/partners/bookings' },
                    { label: 'Analytics', href: '/partners/analytics' },
                    { label: 'Finance', href: '/partners/payments' },
                ]}
            />

            <div className="mb-5 flex flex-wrap items-end gap-3">
                <DateInput
                    className="min-w-[150px]"
                    label="From"
                    value={from}
                    onChange={(value) => {
                        setFrom(value);
                        apply(value, to);
                    }}
                />
                <DateInput
                    className="min-w-[150px]"
                    label="To"
                    value={to}
                    min={from || undefined}
                    onChange={(value) => {
                        setTo(value);
                        apply(from, value);
                    }}
                />
                {from || to ? (
                    <button
                        type="button"
                        className="cursor-pointer rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold text-brand-900"
                        onClick={() => {
                            setFrom('');
                            setTo('');
                            apply('', '');
                        }}
                    >
                        Clear range
                    </button>
                ) : null}
            </div>

            <div className="my-4.5 mb-7 grid grid-cols-2 gap-3 md:grid-cols-4">
                {[
                    ['Finished income', lkr(summary.income_lkr)],
                    ['Potential value', lkr(summary.potential_income_lkr)],
                    ['Commission', lkr(summary.commission_lkr)],
                    ['Net income', lkr(summary.net_income_lkr)],
                    ['Completed', summary.completed],
                    ['Confirmation rate', `${summary.confirmation_rate}%`],
                    ['Guests hosted', summary.guests],
                    ['Avg finished booking', lkr(summary.average_completed_lkr)],
                ].map(([label, value]) => (
                    <div key={label as string} className="rounded-2xl border border-line bg-white p-4.5">
                        <span className="text-[13px] font-bold text-muted">{label}</span>
                        <strong className="block font-display text-2xl">{value}</strong>
                    </div>
                ))}
            </div>

            <h3 className="mb-2 font-sans text-lg font-bold">Monthly income</h3>
            {monthly.length ? (
                <div className="grid grid-cols-6 items-end gap-2 rounded-[14px] bg-cream-dark px-3 pt-4.5 pb-2.5 md:grid-cols-12">
                    {chartMonths.map((month) => (
                        <div key={month.period} className="flex h-40 flex-col items-center justify-end gap-1.5">
                            <div
                                className="w-full max-w-[34px] rounded-t-[7px] bg-brand-600"
                                style={{ height: `${Math.max((month.income_lkr / maxIncome) * 100, 4)}%` }}
                            />
                            <span className="text-[10px] font-extrabold text-brand-900">
                                {month.income_lkr ? lkr(month.income_lkr).replace('Rs. ', '') : ''}
                            </span>
                            <strong className="text-[10px] whitespace-nowrap text-muted">{month.period}</strong>
                        </div>
                    ))}
                </div>
            ) : (
                <p className="text-muted">No booking activity in this range yet.</p>
            )}

            <h3 className="mt-7 mb-2 font-sans text-lg font-bold">Package performance</h3>
            <div className="overflow-x-auto rounded-2xl border border-line bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-[11px] tracking-wide text-muted uppercase">
                            <th className="px-3.5 py-3 font-bold">Package</th>
                            <th className="px-3.5 py-3 font-bold">Bookings</th>
                            <th className="px-3.5 py-3 font-bold">Guests</th>
                            <th className="px-3.5 py-3 font-bold">Income</th>
                            <th className="px-3.5 py-3 font-bold">Conversion</th>
                        </tr>
                    </thead>
                    <tbody>
                        {packages.map((row) => (
                            <tr key={row.id} className="border-t border-line">
                                <td className="px-3.5 py-3 text-sm">
                                    {row.title}
                                    <div className="text-xs text-muted">
                                        {row.category} · {row.active ? 'live' : 'hidden'}
                                    </div>
                                </td>
                                <td className="px-3.5 py-3 text-sm">
                                    {row.bookings}
                                    <div className="text-xs text-muted">
                                        {row.requested} requested · {row.confirmed} confirmed · {row.completed}{' '}
                                        finished · {row.rejected} rejected
                                    </div>
                                </td>
                                <td className="px-3.5 py-3 text-sm">{row.guests}</td>
                                <td className="px-3.5 py-3 text-sm">
                                    {lkr(row.income_lkr)}
                                    <div className="text-xs text-muted">
                                        {lkr(row.potential_income_lkr)} potential
                                    </div>
                                </td>
                                <td className="px-3.5 py-3 text-sm">{row.confirmation_rate}%</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

Analytics.layout = withAppLayout;

export default Analytics;
