import { useState } from 'react';
import { router } from '@inertiajs/react';
import { ChevronDown, SlidersHorizontal, X } from 'lucide-react';
import { index as searchRoute } from '@/actions/App/Http/Controllers/PackageController';
import PackageCard from '@/components/booktrips/package-card';
import Pagination from '@/components/booktrips/pagination';
import SearchBar from '@/components/booktrips/search-bar';
import { withAppLayout } from '@/layouts/app-layout';
import { CATEGORY_LABELS } from '@/lib/booktrips';
import { cn } from '@/lib/utils';
import type { PackageCardData } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type SearchFilters = {
    q: string;
    category: string;
    district: string;
    location: string;
    check_in: string;
    check_out: string;
    minPrice: string;
    maxPrice: string;
    guests: string;
    featured: string;
    sort: string;
};

type SearchProps = {
    packages: PackageCardData[];
    total: number;
    pagination: { page: number; last_page: number; per_page: number };
    categories: Array<{ slug: string; name: string }>;
    destinations: Array<{ name: string }>;
    filters: SearchFilters;
};

function Accordion({
    title,
    open,
    onToggle,
    count,
    children,
}: {
    title: string;
    open: boolean;
    onToggle: () => void;
    count: number;
    children: React.ReactNode;
}) {
    return (
        <div className="border-t border-line">
            <button
                type="button"
                className="flex w-full cursor-pointer items-center justify-between border-0 bg-transparent py-3 text-sm font-extrabold text-ink"
                onClick={onToggle}
            >
                <span>
                    {title}
                    {count ? (
                        <em className="ml-2 inline-grid h-4.5 min-w-4.5 place-items-center rounded-full bg-brand-800 px-1 text-[11px] text-white not-italic">
                            {count}
                        </em>
                    ) : null}
                </span>
                <ChevronDown size={18} className={open ? 'rotate-180' : undefined} />
            </button>
            {open ? <div className="pb-3">{children}</div> : null}
        </div>
    );
}

const Search: InertiaComponent<SearchProps> = ({ packages, total, pagination, categories, destinations, filters }) => {
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [openGroups, setOpenGroups] = useState({ category: true, price: false });

    function patch(next: Partial<SearchFilters>) {
        const merged: Record<string, string> = {};

        (Object.entries({ ...filters, ...next }) as Array<[keyof SearchFilters, string]>).forEach(([key, value]) => {
            if (value && key !== 'sort') {
                merged[key] = String(value);
            }
        });

        if ((next.sort ?? filters.sort) && (next.sort ?? filters.sort) !== 'featured') {
            merged.sort = next.sort ?? filters.sort;
        }

        router.get(searchRoute.url({ query: merged }), {}, { preserveState: true, preserveScroll: true });
    }

    const catLabel = filters.category
        ? categories.find((category) => category.slug === filters.category)?.name ||
          CATEGORY_LABELS[filters.category] ||
          filters.category
        : '';

    const title = filters.location ? `Packages in ${filters.location}` : catLabel || 'All packages';

    const activeFilters = [
        filters.category ? { key: 'category' as const, label: catLabel } : null,
        filters.location ? { key: 'location' as const, label: filters.location } : null,
        filters.minPrice || filters.maxPrice
            ? { key: 'price' as const, label: `Rs. ${filters.minPrice || '0'} – ${filters.maxPrice || 'any'}` }
            : null,
    ].filter((filter): filter is { key: 'category' | 'location' | 'price'; label: string } => Boolean(filter));

    const optionClass = (selected: boolean) =>
        cn('flex cursor-pointer items-center gap-2 rounded-[10px] px-2 py-1.5 text-sm', selected && 'bg-brand-50 font-bold text-brand-900');

    return (
        <div className="mx-auto grid w-[min(1180px,calc(100%-2rem))] grid-cols-1 gap-6 py-7 pb-14 lg:grid-cols-[280px_1fr]">
            <aside className={cn('h-fit rounded-2xl border border-line bg-white p-4.5 lg:sticky lg:top-22', filtersOpen && 'open')}>
                <button
                    type="button"
                    className="flex w-full cursor-pointer items-center justify-between rounded-2xl border border-line bg-white px-3.5 py-3 font-extrabold text-ink lg:hidden"
                    onClick={() => setFiltersOpen((value) => !value)}
                >
                    <span className="flex items-center gap-2">
                        <SlidersHorizontal size={16} /> Filter {activeFilters.length ? `(${activeFilters.length})` : ''}
                    </span>
                    <ChevronDown size={18} className={filtersOpen ? 'rotate-180' : undefined} />
                </button>
                <div className={cn('mt-2.5 hidden lg:block', filtersOpen && 'block')}>
                    <div className="mb-2 flex items-center justify-between">
                        <h3 className="font-sans text-base font-bold">Filter by</h3>
                        {activeFilters.length ? (
                            <button
                                type="button"
                                className="cursor-pointer border-0 bg-transparent text-[13px] font-extrabold text-brand-800"
                                onClick={() => patch({ category: '', minPrice: '', maxPrice: '', location: '' })}
                            >
                                Clear
                            </button>
                        ) : null}
                    </div>

                    <Accordion
                        title="Category"
                        count={filters.category ? 1 : 0}
                        open={openGroups.category}
                        onToggle={() => setOpenGroups((groups) => ({ ...groups, category: !groups.category }))}
                    >
                        <label className={optionClass(!filters.category)}>
                            <input
                                type="radio"
                                name="category"
                                checked={!filters.category}
                                onChange={() => patch({ category: '' })}
                            />
                            All categories
                        </label>
                        {(categories.length
                            ? categories
                            : Object.entries(CATEGORY_LABELS).map(([slug, name]) => ({ slug, name }))
                        ).map((category) => (
                            <label key={category.slug} className={optionClass(filters.category === category.slug)}>
                                <input
                                    type="radio"
                                    name="category"
                                    checked={filters.category === category.slug}
                                    onChange={() => patch({ category: category.slug })}
                                />
                                {category.name}
                            </label>
                        ))}
                    </Accordion>

                    <Accordion
                        title="Price (Rs.)"
                        count={filters.minPrice || filters.maxPrice ? 1 : 0}
                        open={openGroups.price}
                        onToggle={() => setOpenGroups((groups) => ({ ...groups, price: !groups.price }))}
                    >
                        <div className="grid gap-2">
                            <input
                                className="input-base"
                                placeholder="Min"
                                value={filters.minPrice}
                                onChange={(event) => patch({ minPrice: event.target.value })}
                            />
                            <input
                                className="input-base"
                                placeholder="Max"
                                value={filters.maxPrice}
                                onChange={(event) => patch({ maxPrice: event.target.value })}
                            />
                        </div>
                    </Accordion>
                </div>
            </aside>

            <div>
                <SearchBar
                    destinations={destinations}
                    initial={{
                        location: filters.location,
                        check_in: filters.check_in,
                        check_out: filters.check_out,
                        guests: filters.guests || '2',
                    }}
                />

                {activeFilters.length ? (
                    <div className="my-3.5 mb-2 flex flex-wrap gap-2">
                        {activeFilters.map((filter) => (
                            <button
                                key={filter.key}
                                type="button"
                                className="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-brand-100 bg-brand-50 px-2.5 py-1.5 text-[13px] font-bold text-brand-900"
                                onClick={() =>
                                    patch(filter.key === 'price' ? { minPrice: '', maxPrice: '' } : { [filter.key]: '' })
                                }
                            >
                                {filter.label} <X size={14} />
                            </button>
                        ))}
                    </div>
                ) : null}

                <div className="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h1 className="text-[28px]">{title}</h1>
                        <p className="text-muted">{total} packages</p>
                    </div>
                    <select
                        className="input-base w-auto cursor-pointer"
                        value={filters.sort || 'featured'}
                        onChange={(event) => patch({ sort: event.target.value })}
                    >
                        <option value="featured">Featured</option>
                        <option value="rating">Top rated</option>
                        <option value="price_asc">Price: low to high</option>
                        <option value="price_desc">Price: high to low</option>
                    </select>
                </div>

                <div className="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(240px,1fr))]">
                    {packages.map((pkg) => (
                        <PackageCard key={pkg.id} pkg={pkg} />
                    ))}
                </div>

                {packages.length === 0 ? (
                    <p className="mt-6 text-muted">No packages match those filters. Try another town or category.</p>
                ) : null}

                <Pagination page={pagination.page} lastPage={pagination.last_page} total={total} />
            </div>
        </div>
    );
};

Search.layout = withAppLayout;

export default Search;
