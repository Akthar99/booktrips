import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    destroy,
    publish,
} from '@/actions/App/Http/Controllers/Partner/PartnerPackageController';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import SharePackage from '@/components/booktrips/share-package';
import StatusBadge from '@/components/booktrips/status-badge';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { PackageCardData, SharedProps } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';
import { usePage } from '@inertiajs/react';

type DashboardProps = {
    stats: { packages: number; bookings: number; upcoming: number; requested: number };
    packages: PackageCardData[];
};

const Dashboard: InertiaComponent<DashboardProps> = ({ stats, packages }) => {
    const { auth } = usePage<SharedProps>().props;
    const business = auth.user?.business;
    const [hideId, setHideId] = useState<number | null>(null);
    const [busy, setBusy] = useState(false);

    function hidePackage(id: number) {
        setBusy(true);
        router.delete(destroy.url(id), {
            preserveScroll: true,
            onFinish: () => {
                setBusy(false);
                setHideId(null);
            },
        });
    }

    function publishPackage(id: number) {
        setBusy(true);
        router.patch(publish.url(id), {}, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <div className="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h1 className="text-4xl">{business?.name || 'Business admin'}</h1>
                    <p className="text-muted">
                        Property extranet · {business?.city} · {business?.type?.replace('_', ' ')}
                    </p>
                </div>
                <Link
                    href="/partners/packages/new"
                    className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                >
                    Add package
                </Link>
            </div>
            <Tabs
                items={[
                    { label: 'Overview', href: '/partners/dashboard', exact: true },
                    { label: 'Reservations', href: '/partners/bookings' },
                    { label: 'Analytics', href: '/partners/analytics' },
                    { label: 'Finance', href: '/partners/payments' },
                ]}
            />
            <div className="my-4.5 mb-7 grid grid-cols-2 gap-3 md:grid-cols-3">
                {[
                    ['Packages', stats.packages],
                    ['Bookings', stats.bookings],
                    ['Upcoming', stats.upcoming],
                ].map(([label, value]) => (
                    <div key={label as string} className="rounded-2xl border border-line bg-white p-4.5">
                        <span className="text-[13px] font-bold text-muted">{label}</span>
                        <strong className="block font-display text-[28px]">{value}</strong>
                    </div>
                ))}
            </div>
            {stats.requested ? (
                <div className="mb-4 rounded-xl bg-orange-50 px-3 py-2.5 text-[13px] text-warn">
                    {stats.requested} request{stats.requested === 1 ? '' : 's'} waiting to be confirmed.{' '}
                    <Link className="font-bold underline" href="/partners/bookings?status=requested">
                        Review now
                    </Link>
                </div>
            ) : null}
            <h3 className="mb-3 font-sans text-lg font-bold">Your packages</h3>
            <div className="overflow-x-auto rounded-2xl border border-line bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-[11px] tracking-wide text-muted uppercase">
                            <th className="px-3.5 py-3 font-bold">Title</th>
                            <th className="px-3.5 py-3 font-bold">Category</th>
                            <th className="px-3.5 py-3 font-bold">Price</th>
                            <th className="px-3.5 py-3 font-bold">Status</th>
                            <th className="px-3.5 py-3 text-right font-bold" />
                        </tr>
                    </thead>
                    <tbody>
                        {packages.map((pkg) => (
                            <tr key={pkg.id} className="border-t border-line">
                                <td className="px-3.5 py-3 text-sm">
                                    <Link className="font-bold text-brand-800" href={`/packages/${pkg.slug || pkg.id}`}>
                                        {pkg.title}
                                    </Link>
                                </td>
                                <td className="px-3.5 py-3 text-sm capitalize">{pkg.category}</td>
                                <td className="px-3.5 py-3 text-sm">
                                    {pkg.discount_active ? (
                                        <del className="text-muted">{lkr(pkg.price_lkr)}</del>
                                    ) : null}{' '}
                                    {lkr(pkg.display_price_lkr ?? pkg.price_lkr)}
                                    {pkg.discount_active ? (
                                        <div className="text-xs font-bold text-[#a44a00]">{pkg.discount_label}</div>
                                    ) : null}
                                </td>
                                <td className="px-3.5 py-3">
                                    <StatusBadge status={pkg.active ? 'active' : 'suspended'} />
                                </td>
                                <td className="px-3.5 py-3 text-right whitespace-nowrap">
                                    <SharePackage
                                        id={pkg.id}
                                        slug={pkg.slug}
                                        title={pkg.title}
                                        className="mr-3 inline-flex cursor-pointer items-center gap-1 border-0 bg-transparent p-0 text-[13px] font-bold text-brand-800"
                                    />
                                    <Link
                                        className="text-[13px] font-bold text-brand-800"
                                        href={`/partners/packages/${pkg.id}/edit`}
                                    >
                                        Edit
                                    </Link>
                                    {pkg.active ? (
                                        <button
                                            type="button"
                                            className="ml-3 cursor-pointer border-0 bg-transparent text-[13px] font-bold text-brand-900"
                                            onClick={() => setHideId(pkg.id)}
                                        >
                                            Hide
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            className="ml-3 cursor-pointer border-0 bg-transparent text-[13px] font-bold text-brand-800"
                                            disabled={busy}
                                            onClick={() => publishPackage(pkg.id)}
                                        >
                                            List again
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <ConfirmDialog
                open={hideId !== null}
                title="Hide this package?"
                message="It will no longer appear in public search. You can publish it again later."
                confirmLabel="Hide package"
                danger
                busy={busy}
                onConfirm={() => hideId !== null && hidePackage(hideId)}
                onCancel={() => setHideId(null)}
            />
        </div>
    );
};

Dashboard.layout = withAppLayout;

export default Dashboard;
