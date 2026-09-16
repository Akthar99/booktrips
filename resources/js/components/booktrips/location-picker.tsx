import { useEffect, useState } from 'react';
import { MapPin, X } from 'lucide-react';
import { search as geoSearch } from '@/actions/App/Http/Controllers/GeoController';
import MapView from '@/components/booktrips/map-view';
import { Field, Input } from '@/components/booktrips/field';

type GeoHit = { label: string; lat: number; lng: number; name: string };

type Patch = { location: string; address: string; lat: number | null; lng: number | null };

export default function LocationPicker({
    value,
    onChange,
}: {
    value: Patch;
    onChange: (patch: Patch) => void;
}) {
    const [query, setQuery] = useState(value.address || value.location || '');
    const [hits, setHits] = useState<GeoHit[]>([]);

    useEffect(() => {
        setQuery(value.address || value.location || '');
    }, [value.address, value.location]);

    useEffect(() => {
        if (query.trim().length < 2) {
            setHits([]);

            return undefined;
        }

        const timer = setTimeout(() => {
            fetch(geoSearch.url({ query: { q: query } }), { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data: { results?: GeoHit[] }) => setHits(data.results ?? []))
                .catch(() => setHits([]));
        }, 350);

        return () => clearTimeout(timer);
    }, [query]);

    function pick(hit: GeoHit) {
        onChange({ location: hit.name, address: hit.label, lat: hit.lat, lng: hit.lng });
        setQuery(hit.label);
        setHits([]);
    }

    /**
     * Tapping the map is the reliable way to pin a place — it works even when
     * the address lookup service is unavailable. The typed name is kept.
     */
    function pickOnMap(lat: number, lng: number) {
        onChange({
            location: value.location || value.address || 'Pinned location',
            address: value.address || value.location || '',
            lat: Number(lat.toFixed(6)),
            lng: Number(lng.toFixed(6)),
        });
        setHits([]);
    }

    const pinned = value.lat !== null && value.lng !== null && (value.lat !== 0 || value.lng !== 0);
    const pin = pinned
        ? [{ lat: value.lat as number, lng: value.lng as number, title: value.location, location: value.address }]
        : [];

    return (
        <Field label="Location on map" className="mb-3">
            <Input
                required
                value={query}
                placeholder="Search an address in Sri Lanka"
                onChange={(event) => {
                    const text = event.target.value;

                    setQuery(text);
                    // Typing invalidates any previously picked coordinates.
                    onChange({ location: text, address: text, lat: null, lng: null });
                }}
            />
            {hits.length ? (
                <div className="mt-1.5 max-h-[220px] overflow-y-auto rounded-xl border border-line bg-white">
                    {hits.map((hit) => (
                        <button
                            type="button"
                            key={`${hit.lat}-${hit.lng}`}
                            className="block w-full cursor-pointer border-b border-line bg-white px-3 py-2.5 text-left text-[13px] hover:bg-brand-50"
                            onClick={() => pick(hit)}
                        >
                            {hit.label}
                        </button>
                    ))}
                </div>
            ) : null}
            <p className="mt-1.5 text-[12px] text-muted">
                Pick a suggestion above, or click anywhere on the map to drop the pin yourself.
            </p>
            <div className="mt-2.5">
                <MapView
                    pins={pin}
                    center={pin[0] ? [pin[0].lat, pin[0].lng] : [7.87, 80.77]}
                    zoom={pin.length ? 13 : 7}
                    height={220}
                    markerStyle="dot"
                    onMapClick={pickOnMap}
                />
            </div>
            {pinned ? (
                <div className="mt-2 flex items-center gap-2 text-[12px] text-muted">
                    <MapPin size={14} className="text-brand-800" />
                    <span>
                        Pinned at {value.lat}, {value.lng}
                    </span>
                    <button
                        type="button"
                        className="inline-flex cursor-pointer items-center gap-1 border-0 bg-transparent p-0 text-[12px] font-bold text-brand-800"
                        onClick={() => onChange({ ...value, lat: null, lng: null })}
                    >
                        <X size={13} /> Clear pin
                    </button>
                </div>
            ) : (
                <p className="mt-2 text-[12px] font-semibold text-warn">
                    No pin yet — travellers will not see this package on the map.
                </p>
            )}
        </Field>
    );
}
