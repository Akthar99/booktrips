import { useEffect, useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Check, Clock, MapPin, Users } from 'lucide-react';
import { show as packageShow, quote as quoteRoute } from '@/actions/App/Http/Controllers/PackageController';
import Alert from '@/components/booktrips/alert';
import DateInput from '@/components/booktrips/date-input';
import { Select } from '@/components/booktrips/field';
import ImageGallery from '@/components/booktrips/image-gallery';
import MapView from '@/components/booktrips/map-view';
import SharePackage from '@/components/booktrips/share-package';
import StarRating from '@/components/booktrips/star-rating';
import { withAppLayout } from '@/layouts/app-layout';
import { CATEGORY_LABELS, durationLabel, lkr, priceHint, ratingWord } from '@/lib/booktrips';
import type { PackageDetailData, ReviewData, SharedProps } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type Quote = {
    base_total_lkr: number;
    discount_lkr: number;
    discount_active: boolean;
    discount_label: string;
    total_lkr: number;
};

type PackageShowProps = {
    package: PackageDetailData;
    reviews: ReviewData[];
};

const FALLBACK_IMAGE = 'https://images.unsplash.com/photo-1500530855697-b816dceb13d4?w=1400';

const PackageShow: InertiaComponent<PackageShowProps> = ({ package: pkg, reviews }) => {
    const page = usePage<SharedProps>();
    const user = page.props.auth.user;

    const initial = useMemo(() => {
        const query = new URLSearchParams(page.url.split('?')[1] ?? '');

        return {
            check_in: query.get('check_in') ?? '',
            check_out: query.get('check_out') ?? '',
            guests: query.get('guests') ?? '2',
        };
    }, [page.url]);

    const [form, setForm] = useState(initial);
    const [quote, setQuote] = useState<Quote | null>(null);
    const [quoteError, setQuoteError] = useState('');

    useEffect(() => {
        setForm(initial);
    }, [initial]);

    useEffect(() => {
        if (!form.check_in || !form.check_out) {
            setQuote(null);
            setQuoteError('');

            return undefined;
        }

        const controller = new AbortController();

        fetch(
            quoteRoute.url(pkg.id, {
                query: { check_in: form.check_in, check_out: form.check_out, guests: form.guests },
            }),
            { headers: { Accept: 'application/json' }, signal: controller.signal },
        )
            .then(async (response) => {
                const data = (await response.json().catch(() => ({}))) as Record<string, unknown>;

                if (!response.ok) {
                    setQuote(null);
                    setQuoteError(
                        (data.message as string) ||
                            ((data.errors as Record<string, string[]> | undefined)?.check_in?.[0] ?? 'Those dates are not available.'),
                    );

                    return;
                }

                setQuote(data as unknown as Quote);
                setQuoteError('');
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [pkg.id, form.check_in, form.check_out, form.guests]);

    function reserve() {
        if (!user) {
            router.get('/login', { next: packageShow.url(pkg.slug || pkg.id) });

            return;
        }

        router.get(`/book/${pkg.id}`, {
            check_in: form.check_in,
            check_out: form.check_out,
            guests: form.guests,
        });
    }

    const images = pkg.images?.length ? pkg.images : [FALLBACK_IMAGE];
    const guestOptions = Array.from({ length: pkg.max_guests }, (_, index) => index + 1).filter(
        (n) => n >= pkg.min_guests,
    );

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-6 pb-16">
            <div className="mb-3.5 text-[13px] text-muted">
                <Link href="/">Home</Link> ·{' '}
                <Link href={`/search?category=${pkg.category}`}>{CATEGORY_LABELS[pkg.category]}</Link> ·{' '}
                {pkg.location}
            </div>
            <ImageGallery images={images} title={pkg.title} fallback={FALLBACK_IMAGE} />

            <div className="mt-6 grid grid-cols-1 gap-7 lg:grid-cols-[1fr_340px]">
                <div>
                    <div className="flex items-center gap-1 text-muted">
                        <MapPin size={16} /> {pkg.location}, {pkg.district}
                    </div>
                    <h1 className="my-2 text-4xl">{pkg.title}</h1>
                    <div className="my-2 mb-3 flex items-center gap-2">
                        <StarRating value={pkg.rating} readOnly />
                        <div>
                            <em className="text-xs font-bold not-italic">{ratingWord(pkg.rating)}</em>
                            <div>
                                <span className="text-xs text-muted">
                                    {pkg.review_count} reviews · {pkg.host?.name}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="my-3 flex flex-wrap gap-2">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-2.5 py-1.5 text-[13px] font-semibold">
                            {CATEGORY_LABELS[pkg.category]}
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-2.5 py-1.5 text-[13px] font-semibold">
                            <Clock size={14} /> {durationLabel(pkg)}
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-2.5 py-1.5 text-[13px] font-semibold">
                            <Users size={14} /> {pkg.min_guests}–{pkg.max_guests} guests
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-2.5 py-1.5 text-[13px] font-semibold">
                            Pay at destination
                        </span>
                        <SharePackage
                            id={pkg.id}
                            slug={pkg.slug}
                            title={pkg.title}
                            className="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-line bg-white px-2.5 py-1.5 text-[13px] font-semibold text-brand-900 transition hover:border-brand-700"
                        />
                    </div>
                    {pkg.highlight ? (
                        <p className="text-[17px] text-[#3f3f46]">{pkg.highlight}</p>
                    ) : null}
                    <div className="mt-2 text-[15px] text-[#3f3f46]">
                        {pkg.description.split('\n').map((paragraph, index) => (
                            <p key={index} className={index ? 'mt-2.5' : undefined}>
                                {paragraph}
                            </p>
                        ))}
                    </div>

                    {pkg.lat && pkg.lng ? (
                        <div className="my-5">
                            <h3 className="mb-2 text-xl">On the map</h3>
                            <MapView
                                pins={[
                                    {
                                        id: pkg.id,
                                        title: pkg.title,
                                        lat: pkg.lat,
                                        lng: pkg.lng,
                                        location: pkg.location,
                                    },
                                ]}
                                zoom={12}
                                height={240}
                            />
                        </div>
                    ) : null}

                    <h3 className="mt-7 mb-2 text-xl">What’s included</h3>
                    <ul className="my-3 grid list-none gap-2 p-0">
                        {pkg.included.map((item) => (
                            <li key={item} className="flex items-start gap-2">
                                <Check size={16} color="#2D6A4F" /> {item}
                            </li>
                        ))}
                    </ul>
                    {pkg.excluded.length ? (
                        <>
                            <h3 className="mt-5 mb-2 text-xl">Not included</h3>
                            <ul className="my-3 grid list-none gap-2 p-0">
                                {pkg.excluded.map((item) => (
                                    <li key={item}>– {item}</li>
                                ))}
                            </ul>
                        </>
                    ) : null}

                    {pkg.itinerary.length ? (
                        <>
                            <h3 className="mt-5 mb-2 text-xl">Plan</h3>
                            <div className="my-3 mb-6 grid gap-3">
                                {pkg.itinerary.map((day) => (
                                    <div
                                        key={day.day}
                                        className="rounded-[14px] border border-line bg-white px-4 py-3.5"
                                    >
                                        <strong className="mb-1 block">
                                            Day {day.day} · {day.title}
                                        </strong>
                                        <span className="text-muted">{day.description}</span>
                                    </div>
                                ))}
                            </div>
                        </>
                    ) : null}

                    <h3 className="my-2 text-xl">Reviews</h3>
                    {reviews.length === 0 ? <p className="text-muted">No reviews yet.</p> : null}
                    {reviews.map((review) => (
                        <div
                            key={review.id}
                            className="mb-2 rounded-[14px] border border-line bg-white px-4 py-3.5"
                        >
                            <div className="flex items-center gap-1.5">
                                <strong>{review.title || 'Traveller review'}</strong>
                                <StarRating value={review.rating} readOnly size={14} />
                            </div>
                            <div className="text-[13px] text-muted">{review.user_name}</div>
                            <p>{review.comment}</p>
                        </div>
                    ))}
                </div>

                <aside className="h-fit rounded-card border border-line bg-white p-4.5 shadow-card lg:sticky lg:top-22">
                    <div className="text-[22px] font-extrabold text-brand-900">
                        {pkg.discount_active ? (
                            <del className="block text-xs font-semibold text-muted">{lkr(pkg.price_lkr)}</del>
                        ) : null}
                        {lkr(pkg.display_price_lkr ?? pkg.price_lkr)}
                        <small className="block text-[11px] font-semibold text-muted">
                            {priceHint(pkg)}
                            {pkg.discount_active ? ` · ${pkg.discount_label}` : ''}
                        </small>
                    </div>
                    <div className="mt-3">
                        <DateInput
                            label="Start"
                            value={form.check_in}
                            onChange={(check_in) => setForm({ ...form, check_in })}
                        />
                        <DateInput
                            label="End"
                            value={form.check_out}
                            min={form.check_in || undefined}
                            onChange={(check_out) => setForm({ ...form, check_out })}
                        />
                        <label className="mb-3 flex flex-col gap-1.5">
                            <span className="text-xs font-bold tracking-wide text-muted uppercase">Guests</span>
                            <Select
                                value={form.guests}
                                onChange={(event) => setForm({ ...form, guests: event.target.value })}
                            >
                                {guestOptions.map((n) => (
                                    <option key={n} value={n}>
                                        {n}
                                    </option>
                                ))}
                            </Select>
                        </label>
                    </div>
                    {quoteError ? <Alert tone="error">{quoteError}</Alert> : null}
                    {quote ? (
                        <div className="my-2 mb-3.5 rounded-xl bg-brand-50 px-3 py-2.5 text-[13px] text-brand-950">
                            {quote.discount_lkr ? (
                                <>
                                    <del>{lkr(quote.base_total_lkr)}</del> →{' '}
                                </>
                            ) : null}
                            Total to pay at destination: <strong>{lkr(quote.total_lkr)}</strong>
                            {quote.discount_label ? (
                                <small className="mt-1 block">{quote.discount_label}</small>
                            ) : null}
                        </div>
                    ) : (
                        <div className="my-2 mb-3.5 rounded-xl bg-brand-50 px-3 py-2.5 text-[13px] text-brand-950">
                            Choose dates to see the amount you pay on arrival.
                        </div>
                    )}
                    <button
                        type="button"
                        className="w-full cursor-pointer rounded-full bg-brand-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:cursor-not-allowed disabled:opacity-55"
                        onClick={reserve}
                        disabled={!form.check_in || !form.check_out}
                    >
                        {user ? 'Reserve' : 'Log in to reserve'}
                    </button>
                    <p className="mt-2.5 text-xs text-muted">{pkg.cancellation_policy}</p>
                    {pkg.meeting_point ? (
                        <p className="text-xs text-muted">Meet: {pkg.meeting_point}</p>
                    ) : null}
                </aside>
            </div>
        </div>
    );
};

PackageShow.layout = withAppLayout;

export default PackageShow;
