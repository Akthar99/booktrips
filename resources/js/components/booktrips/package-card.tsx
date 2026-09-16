import { Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { show as packageShow } from '@/actions/App/Http/Controllers/PackageController';
import StarRating from '@/components/booktrips/star-rating';
import { CATEGORY_LABELS, durationLabel, lkr, priceHint, ratingWord } from '@/lib/booktrips';
import type { PackageCardData } from '@/types/booktrips';

export default function PackageCard({ pkg }: { pkg: PackageCardData }) {
    const image = pkg.images?.[0];

    return (
        <Link
            href={packageShow.url(pkg.slug || pkg.id)}
            className="group flex flex-col overflow-hidden rounded-card border border-line bg-white transition duration-200 hover:-translate-y-1 hover:shadow-card"
        >
            <div className="relative aspect-[4/3] overflow-hidden bg-cream-dark">
                {image ? (
                    <img
                        src={image}
                        alt={pkg.title}
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        loading="lazy"
                    />
                ) : null}
                <span className="absolute top-3 left-3 rounded-full bg-white/92 px-2 py-1 text-[11px] font-extrabold tracking-wide text-brand-900 uppercase">
                    {CATEGORY_LABELS[pkg.category] || pkg.category}
                </span>
            </div>
            <div className="flex flex-1 flex-col gap-1.5 p-3.5 pb-4">
                <div className="flex items-center gap-1 text-[13px] font-semibold text-muted">
                    <MapPin size={14} /> {pkg.location}
                </div>
                <h3 className="font-sans text-base font-bold tracking-[-0.02em]">{pkg.title}</h3>
                <div className="text-[13px] font-semibold text-muted">{durationLabel(pkg)}</div>
                <div className="flex items-center gap-2">
                    <StarRating value={pkg.rating} readOnly size={15} />
                    <div>
                        <em className="text-xs font-bold not-italic">{ratingWord(pkg.rating)}</em>
                        <div>
                            <span className="text-xs text-muted">{pkg.review_count || 0} reviews</span>
                        </div>
                    </div>
                </div>
                <div className="mt-auto flex items-end justify-between gap-2 pt-2.5">
                    <div className="text-base font-extrabold text-brand-900">
                        {pkg.discount_active ? (
                            <del className="block text-xs font-semibold text-muted">{lkr(pkg.price_lkr)}</del>
                        ) : null}
                        {lkr(pkg.display_price_lkr ?? pkg.price_lkr)}
                        <small className="block text-[11px] font-semibold text-muted">{priceHint(pkg)}</small>
                    </div>
                    {pkg.discount_active ? (
                        <span className="inline-flex items-center rounded-full bg-[#fff0df] px-2 py-1.5 text-xs font-extrabold whitespace-nowrap text-[#a44a00]">
                            {pkg.discount_label}
                        </span>
                    ) : (
                        <span className="inline-flex items-center rounded-full bg-brand-50 px-2 py-1 text-xs font-bold text-brand-900">
                            Pay at destination
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}
