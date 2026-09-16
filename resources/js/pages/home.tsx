import { Link } from '@inertiajs/react';
import { Compass } from 'lucide-react';
import MapView, { type MapPin } from '@/components/booktrips/map-view';
import PackageCard from '@/components/booktrips/package-card';
import SearchBar from '@/components/booktrips/search-bar';
import { withAppLayout } from '@/layouts/app-layout';
import { CATEGORY_ICONS } from '@/lib/booktrips';
import type { AuthUser, PackageCardData } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

const DEST_SHOTS = [
    { name: 'Ella', img: 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=1200&q=80' },
    { name: 'Yala', img: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=800&q=80' },
    { name: 'Galle', img: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80' },
    { name: 'Mirissa', img: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&q=80' },
    { name: 'Sigiriya', img: 'https://images.unsplash.com/photo-1548013146-72479768bada?w=800&q=80' },
];

type Category = { slug: string; name: string; blurb: string; icon: string };

type HomeProps = {
    featured: PackageCardData[];
    pins: MapPin[];
    categories: Category[];
    destinations: Array<{ name: string }>;
    packageCount: number;
    auth: { user: AuthUser | null };
};

const Home: InertiaComponent<HomeProps> = ({ featured, pins, categories, destinations }) => {
    return (
        <>
            <section className="relative flex min-h-[540px] items-end py-10 pb-14 text-white md:min-h-[620px]">
                <div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{
                        backgroundImage:
                            "url('https://images.unsplash.com/photo-1593693397690-362cb3027654?w=2000&q=80')",
                    }}
                />
                <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(7,18,33,0.25)_0%,rgba(7,18,33,0.55)_55%,rgba(7,18,33,0.78)_100%)]" />
                <div className="relative z-10 mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <div className="mb-3 text-[13px] font-bold tracking-[0.12em] text-brand-500 uppercase">
                        Sri Lanka
                    </div>
                    <h1 className="mb-3 max-w-[16ch] text-4xl text-white md:text-6xl">
                        Plan the day out. Book the night away.
                    </h1>
                    <p className="mb-7 max-w-[46ch] text-[17px] text-[#e8efe9]">
                        Camping, villas, hotel packages and activity days from operators across the island. You
                        reserve here. You pay when you arrive.
                    </p>
                    <SearchBar
                        destinations={destinations}
                        initial={{ location: '', check_in: '', check_out: '', guests: '2' }}
                    />
                </div>
            </section>

            <section className="py-14">
                <div className="mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <div className="mb-6 flex items-end justify-between gap-4">
                        <div>
                            <h2 className="text-3xl">Browse by mood</h2>
                            <p className="text-muted">Same idea as a hotel search — but for trips, not only rooms.</p>
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                        {categories.map((category) => {
                            const Icon = CATEGORY_ICONS[category.icon] ?? Compass;

                            return (
                                <Link
                                    key={category.slug}
                                    href={`/search?category=${category.slug}`}
                                    className="flex min-h-[118px] flex-col gap-2 rounded-2xl border border-line bg-white px-3.5 py-4 transition hover:-translate-y-0.5 hover:border-brand-500 hover:shadow-card"
                                >
                                    <span className="grid h-9.5 w-9.5 place-items-center rounded-[10px] bg-brand-50 text-brand-800">
                                        <Icon size={18} />
                                    </span>
                                    <strong className="text-sm">{category.name}</strong>
                                    <em className="text-xs font-normal text-muted not-italic">{category.blurb}</em>
                                </Link>
                            );
                        })}
                    </div>
                </div>
            </section>

            <section className="pb-14">
                <div className="mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <div className="mb-6 flex items-end justify-between gap-4">
                        <div>
                            <h2 className="text-3xl">Featured this season</h2>
                            <p className="text-muted">Hand-picked packages with strong reviews.</p>
                        </div>
                        <Link href="/search" className="text-sm font-bold text-brand-800">
                            See all packages
                        </Link>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {featured.map((pkg) => (
                            <PackageCard key={pkg.id} pkg={pkg} />
                        ))}
                    </div>
                </div>
            </section>

            <section className="pb-14">
                <div className="mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <div className="mb-6 flex items-end justify-between gap-4">
                        <div>
                            <h2 className="text-3xl">Near you</h2>
                            <p className="text-muted">Every live package on the map. Open it to sort by distance.</p>
                        </div>
                        <Link href="/map" className="text-sm font-bold text-brand-800">
                            Open map
                        </Link>
                    </div>
                    <MapView pins={pins} height={340} zoom={7} />
                </div>
            </section>

            <section className="pb-14">
                <div className="mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <h2 className="mb-6 text-3xl">Where Sri Lanka goes on weekends</h2>
                    <div className="grid auto-rows-[180px] grid-cols-2 gap-3 md:grid-cols-[2fr_1fr_1fr]">
                        {DEST_SHOTS.map((destination, index) => (
                            <Link
                                key={destination.name}
                                href={`/search?location=${encodeURIComponent(destination.name)}`}
                                className={
                                    'relative overflow-hidden rounded-2xl text-white ' +
                                    (index === 0 ? 'col-span-2 md:col-span-1 md:row-span-2' : '')
                                }
                            >
                                <img src={destination.img} alt={destination.name} className="h-full w-full object-cover" />
                                <div className="absolute inset-x-0 bottom-0 bg-[linear-gradient(transparent,rgba(7,18,33,0.75))] p-4 font-extrabold">
                                    {destination.name}
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="bg-navy-900 py-16 text-[#e8efe9]">
                <div className="mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <h2 className="mb-2.5 text-4xl text-white">How BookTrips works</h2>
                    <p className="max-w-[50ch] text-[#b7c4d1]">
                        No card on file. A verified email is enough to hold the date.
                    </p>
                    <div className="mt-7 grid gap-4.5 md:grid-cols-3">
                        {[
                            ['01', 'Find a package', 'Filter by camping, day out, villa, hotel or activity — then pick dates.'],
                            ['02', 'Verify & reserve', 'Log in, confirm your email, and the host sees your booking code.'],
                            ['03', 'Pay at destination', 'Settle in rupees with the hotel, villa or guide when you arrive.'],
                        ].map(([step, title, copy]) => (
                            <div key={step} className="rounded-2xl bg-navy-800 p-5.5">
                                <span className="mb-2 block font-extrabold text-brand-500">{step}</span>
                                <h3 className="mb-1.5 font-sans text-lg text-white">{title}</h3>
                                <p className="text-[#b7c4d1]">{copy}</p>
                            </div>
                        ))}
                    </div>
                    <div className="mt-7">
                        <Link
                            href="/register"
                            className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                        >
                            Create a free account
                        </Link>
                    </div>
                </div>
            </section>

            <section className="py-14">
                <div className="mx-auto grid w-[min(1180px,calc(100%-2rem))] items-center gap-7 lg:grid-cols-[1.2fr_1fr]">
                    <div>
                        <h2 className="text-3xl">Hotels, villas and operators</h2>
                        <p className="mt-3 mb-5 max-w-[520px] text-muted">
                            If you already host guests in Ella, run jeeps in Yala, or cook camp meals in the
                            Knuckles, list a package. Travellers book it. They pay you on site.
                        </p>
                        <Link
                            href="/partners"
                            className="inline-flex rounded-full bg-navy-900 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-navy-950"
                        >
                            List your package
                        </Link>
                    </div>
                    <img
                        alt="Camp"
                        className="h-70 w-full rounded-[20px] object-cover"
                        src="https://images.unsplash.com/photo-1478131143131-4f7aa68cbfd9?w=1000&q=80"
                    />
                </div>
            </section>
        </>
    );
};

Home.layout = withAppLayout;

export default Home;
