import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { update as updatePackage } from '@/actions/App/Http/Controllers/Admin/AdminPackageController';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import { Input } from '@/components/booktrips/field';
import Pagination from '@/components/booktrips/pagination';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type ListingRow = {
    id: number;
    title: string;
    slug: string;
    category: string;
    location: string;
    price_lkr: number;
    active: boolean;
    featured: boolean;
    rating: number;
    business_name: string;
};

type ListingsProps = {
    packages: Paginated<ListingRow>;
    filters: { q: string };
};

const AdminListings: InertiaComponent<ListingsProps> = ({ packages, filters }) => {
    const [q, setQ] = useState(filters.q);
    const [unlistTarget, setUnlistTarget] = useState<ListingRow | null>(null);
    const [busy, setBusy] = useState(false);

    function load() {
        router.get('/admin/listings', { q: q || undefined }, { preserveState: true, preserveScroll: true });
    }

    function patch(id: number, body: Record<string, boolean>) {
        setBusy(true);
        router.patch(updatePackage.url(id), body, {
            preserveScroll: true,
            onFinish: () => {
                setBusy(false);
                setUnlistTarget(null);
            },
        });
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
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
            <h2 className="mb-3 text-2xl">Listings</h2>
            <form
                className="my-3 flex gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    load();
                }}
            >
                <Input
                    placeholder="Search title, place, business"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                />
                <button
                    type="submit"
                    className="cursor-pointer rounded-full bg-brand-800 px-4 py-2 text-[13px] font-bold text-white transition hover:bg-brand-900"
                >
                    Search
                </button>
            </form>
            <div className="overflow-x-auto rounded-2xl border border-line bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-[11px] tracking-wide text-muted uppercase">
                            <th className="px-3.5 py-3 font-bold">Package</th>
                            <th className="px-3.5 py-3 font-bold">Business</th>
                            <th className="px-3.5 py-3 font-bold">Place</th>
                            <th className="px-3.5 py-3 font-bold">Price</th>
                            <th className="px-3.5 py-3 font-bold">Live</th>
                            <th className="px-3.5 py-3 font-bold">Featured</th>
                            <th className="px-3.5 py-3 text-right font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {packages.data.map((pkg) => (
                            <tr key={pkg.id} className="border-t border-line">
                                <td className="px-3.5 py-3 text-sm">
                                    <Link className="font-bold text-brand-800" href={`/packages/${pkg.slug || pkg.id}`}>
                                        {pkg.title}
                                    </Link>
                                    <div className="text-xs text-muted">{pkg.category}</div>
                                </td>
                                <td className="px-3.5 py-3 text-sm">{pkg.business_name}</td>
                                <td className="px-3.5 py-3 text-sm">{pkg.location}</td>
                                <td className="px-3.5 py-3 text-sm">{lkr(pkg.price_lkr)}</td>
                                <td className="px-3.5 py-3 text-sm">{pkg.active ? 'Yes' : 'No'}</td>
                                <td className="px-3.5 py-3 text-sm">{pkg.featured ? 'Yes' : 'No'}</td>
                                <td className="px-3.5 py-3 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        className={
                                            'cursor-pointer border-0 bg-transparent text-[13px] font-bold hover:underline ' +
                                            (pkg.active ? 'text-danger' : 'text-brand-800')
                                        }
                                        onClick={() =>
                                            pkg.active ? setUnlistTarget(pkg) : patch(pkg.id, { active: true })
                                        }
                                    >
                                        {pkg.active ? 'Unlist' : 'List'}
                                    </button>
                                    <button
                                        type="button"
                                        className="ml-3.5 cursor-pointer border-0 bg-transparent text-[13px] font-bold text-brand-800 hover:underline"
                                        onClick={() => patch(pkg.id, { featured: !pkg.featured })}
                                    >
                                        {pkg.featured ? 'Unfeature' : 'Feature'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination page={packages.current_page} lastPage={packages.last_page} total={packages.total} />

            <ConfirmDialog
                open={unlistTarget !== null}
                title="Unlist this package?"
                message="Travellers will no longer see it in public search. The owner can list it again later."
                confirmLabel="Unlist package"
                danger
                busy={busy}
                onConfirm={() => unlistTarget && patch(unlistTarget.id, { active: false })}
                onCancel={() => setUnlistTarget(null)}
            />
        </div>
    );
};

AdminListings.layout = withAppLayout;

export default AdminListings;
