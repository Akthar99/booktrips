import { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import Alert from '@/components/booktrips/alert';
import { Input, Select } from '@/components/booktrips/field';
import MapView, { type MapPin } from '@/components/booktrips/map-view';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type MapProps = {
    packages: MapPackage[];
    categories: Array<{ slug: string; name: string }>;
};

type MapPackage = MapPin & {
    title: string;
    location: string;
    category: string;
    price_lkr: number;
};

type PinWithDistance = MapPackage & { km?: number };

function distanceKm(
    a: { lat: number; lng: number },
    b: { lat: number; lng: number },
): number {
    const radius = 6371;
    const dLat = ((b.lat - a.lat) * Math.PI) / 180;
    const dLng = ((b.lng - a.lng) * Math.PI) / 180;
    const s =
        Math.sin(dLat / 2) ** 2 +
        Math.cos((a.lat * Math.PI) / 180) *
            Math.cos((b.lat * Math.PI) / 180) *
            Math.sin(dLng / 2) ** 2;

    return 2 * radius * Math.asin(Math.sqrt(s));
}

const ExploreMap: InertiaComponent<MapProps> = ({ packages, categories }) => {
    const [here, setHere] = useState<{ lat: number; lng: number } | null>(null);
    const [locationState, setLocationState] = useState<
        'idle' | 'loading' | 'granted' | 'denied'
    >('idle');
    const [error, setError] = useState('');
    const [category, setCategory] = useState('all');
    const [query, setQuery] = useState('');
    const [maxDistance, setMaxDistance] = useState('all');
    const requested = useRef(false);

    function requestLocation() {
        if (!navigator.geolocation) {
            setLocationState('denied');
            setError('Location is not available in this browser.');

            return;
        }

        setLocationState('loading');
        setError('');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                setHere({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                });
                setLocationState('granted');
            },
            () => {
                setLocationState('denied');
                setError(
                    'Location permission was not granted. You can still browse the map.',
                );
            },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
        );
    }

    useEffect(() => {
        if (requested.current) {
            return;
        }

        requested.current = true;
        requestLocation();
    }, []);

    const shown = useMemo(() => {
        const text = query.trim().toLowerCase();

        const withDistance: PinWithDistance[] = here
            ? packages.map((pin) => ({ ...pin, km: distanceKm(here, pin) }))
            : packages.map((pin) => ({ ...pin }));

        return withDistance
            .filter((pin) => category === 'all' || pin.category === category)
            .filter(
                (pin) =>
                    !text ||
                    `${pin.title} ${pin.location} ${pin.category}`
                        .toLowerCase()
                        .includes(text),
            )
            .filter(
                (pin) =>
                    maxDistance === 'all' ||
                    !here ||
                    (pin.km ?? 0) <= Number(maxDistance),
            )
            .sort((a, b) =>
                here
                    ? (a.km ?? 0) - (b.km ?? 0)
                    : String(a.title).localeCompare(String(b.title)),
            );
    }, [packages, here, category, query, maxDistance]);

    const suggestions = here ? shown.slice(0, 6) : [];
    const mapPins: MapPin[] = here
        ? [
              {
                  lat: here.lat,
                  lng: here.lng,
                  kind: 'you',
                  title: 'Your location',
              },
              ...shown,
          ]
        : shown;

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <div className="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h1 className="text-4xl">Map</h1>
                    <p className="text-muted">
                        {here
                            ? 'Showing packages nearest to your location first.'
                            : 'Browse packages across Sri Lanka.'}
                    </p>
                </div>
                <button
                    type="button"
                    className="bg-brand-800 hover:bg-brand-900 cursor-pointer rounded-full px-4.5 py-2.5 text-sm font-bold text-white transition disabled:opacity-55"
                    onClick={requestLocation}
                    disabled={locationState === 'loading'}
                >
                    {locationState === 'loading' ? 'Finding you…' : 'Near me'}
                </button>
            </div>
            <div className="border-line my-3.5 flex flex-wrap gap-2 rounded-[14px] border bg-white p-3">
                <Input
                    className="min-w-[150px] flex-1"
                    placeholder="Search packages or areas"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                />
                <Select
                    className="min-w-[150px] flex-1"
                    value={category}
                    onChange={(event) => setCategory(event.target.value)}
                >
                    <option value="all">All categories</option>
                    {categories.map((item) => (
                        <option key={item.slug} value={item.slug}>
                            {item.name}
                        </option>
                    ))}
                </Select>
                {here ? (
                    <Select
                        className="min-w-[150px] flex-1"
                        value={maxDistance}
                        onChange={(event) => setMaxDistance(event.target.value)}
                    >
                        <option value="all">Any distance</option>
                        <option value="25">Within 25 km</option>
                        <option value="50">Within 50 km</option>
                        <option value="100">Within 100 km</option>
                        <option value="250">Within 250 km</option>
                    </Select>
                ) : null}
            </div>
            {error ? <Alert tone="warn">{error}</Alert> : null}
            <MapView pins={mapPins} height={420} zoom={here ? 10 : 7} />
            {here ? (
                <>
                    <div className="mt-7 mb-3.5 flex items-end justify-between gap-4">
                        <div>
                            <h2 className="text-2xl">Nearest packages</h2>
                            <p className="text-muted">
                                Suggestions based on your current location.
                            </p>
                        </div>
                        <span className="text-muted text-[13px]">
                            {shown.length} on map
                        </span>
                    </div>
                    <div className="grid [grid-template-columns:repeat(auto-fill,minmax(220px,1fr))] gap-4">
                        {suggestions.map((pin) => (
                            <Link
                                key={pin.id}
                                href={`/packages/${pin.id}`}
                                className="rounded-card border-line hover:shadow-card border bg-white px-3.5 py-3.5 transition"
                            >
                                <h3 className="font-sans text-base font-bold">
                                    {pin.title}
                                </h3>
                                <div className="text-muted text-[13px] font-semibold">
                                    {pin.location}
                                    {pin.km != null
                                        ? ` · ${pin.km.toFixed(0)} km away`
                                        : ''}
                                </div>
                                <div className="text-brand-900 font-extrabold">
                                    {lkr(pin.price_lkr)}
                                </div>
                            </Link>
                        ))}
                    </div>
                    {suggestions.length === 0 ? (
                        <p className="text-muted text-sm">
                            No nearby packages match these filters.
                        </p>
                    ) : null}
                </>
            ) : (
                <p className="text-muted mt-3.5 text-sm">
                    Allow location access to get nearby package suggestions. The
                    map still shows available packages.
                </p>
            )}
        </div>
    );
};

ExploreMap.layout = withAppLayout;

export default ExploreMap;
