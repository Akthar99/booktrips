import 'leaflet/dist/leaflet.css';
import { useEffect, useRef } from 'react';
import type { Layer, Map as LeafletMap } from 'leaflet';

export type MapPin = {
    id?: number;
    lat: number;
    lng: number;
    title?: string;
    location?: string;
};

type LeafletModule = typeof import('leaflet');

function escapeHtml(value: unknown): string {
    return String(value ?? '').replace(
        /[&<>"']/g,
        (char) =>
            (({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[char] ?? char),
    );
}

export default function MapView({
    pins = [],
    center,
    zoom = 8,
    height = 320,
    onPick,
    onMapClick,
    interactive = true,
    markerStyle = 'label',
    focusCenter = false,
}: {
    pins?: MapPin[];
    center?: [number, number];
    zoom?: number;
    height?: number;
    onPick?: (pin: MapPin) => void;
    onMapClick?: (lat: number, lng: number) => void;
    interactive?: boolean;
    markerStyle?: 'label' | 'dot';
    focusCenter?: boolean;
}) {
    const container = useRef<HTMLDivElement>(null);
    const mapRef = useRef<LeafletMap | null>(null);
    const leafletRef = useRef<LeafletModule | null>(null);
    const layersRef = useRef<Layer[]>([]);
    const clickRef = useRef(onMapClick);
    clickRef.current = onMapClick;

    useEffect(() => {
        let disposed = false;

        async function boot() {
            if (!container.current || mapRef.current) {
                return;
            }

            const leaflet = (await import('leaflet')).default as unknown as LeafletModule;

            if (disposed || !container.current) {
                return;
            }

            leafletRef.current = leaflet;

            const start: [number, number] = center ??
                (pins[0] ? [pins[0].lat, pins[0].lng] : [7.87, 80.77]);

            const map = leaflet.map(container.current, { scrollWheelZoom: interactive }).setView(start, zoom);

            map.on('click', (event) => {
                clickRef.current?.(event.latlng.lat, event.latlng.lng);
            });

            leaflet
                .tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                })
                .addTo(map);

            mapRef.current = map;
            draw();
        }

        function draw() {
            const map = mapRef.current;
            const leaflet = leafletRef.current;

            if (!map || !leaflet) {
                return;
            }

            layersRef.current.forEach((layer) => map.removeLayer(layer));
            layersRef.current = [];

            const bounds: Array<[number, number]> = [];

            pins
                .filter((pin) => pin.lat && pin.lng)
                .forEach((pin) => {
                    const title = escapeHtml(pin.title || pin.location || 'Package');
                    const location = escapeHtml(pin.location || '');
                    const initial = escapeHtml((pin.title || pin.location || 'P').trim().slice(0, 1).toUpperCase());

                    const marker =
                        markerStyle === 'dot'
                            ? leaflet.circleMarker([pin.lat, pin.lng], {
                                  radius: 9,
                                  color: '#0B1D36',
                                  fillColor: '#0B1D36',
                                  fillOpacity: 0.9,
                                  weight: 2,
                              })
                            : leaflet.marker([pin.lat, pin.lng], {
                                  icon: leaflet.divIcon({
                                      className: 'bt-map-label-icon',
                                      html: `<div style="position:relative;transform:translate(-50%,-100%);display:grid;grid-template-columns:28px minmax(72px,max-content);grid-template-rows:auto auto;column-gap:8px;align-items:center;min-width:118px;max-width:190px;padding:7px 10px 7px 7px;background:#0b1d36;color:#fff;border:2px solid #fff;border-radius:11px;box-shadow:0 5px 14px rgba(7,18,33,0.3);cursor:pointer;"><span style="grid-row:1/span 2;display:grid;place-items:center;width:28px;height:28px;border-radius:8px;background:#52b788;color:#fff;font-weight:800;font-size:14px;">${initial}</span><strong style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;line-height:1.2;">${title}</strong>${location ? `<small style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#b7c4d1;font-size:10px;line-height:1.2;">${location}</small>` : ''}</div>`,
                                      iconSize: [0, 0],
                                      iconAnchor: [0, 0],
                                      popupAnchor: [0, -42],
                                  }),
                              }).addTo(map);

                    if (pin.title) {
                        const link = pin.id ? `<a href="/packages/${pin.id}">${title}</a>` : title;
                        marker.bindPopup(`${link}${location ? `<br/>${location}` : ''}`);
                    }

                    if (onPick) {
                        marker.on('click', () => onPick(pin));
                    }

                    bounds.push([pin.lat, pin.lng]);
                });

            if (focusCenter && center) {
                map.setView(center, zoom || 12);
            } else if (bounds.length > 1) {
                map.fitBounds(bounds, { padding: [28, 28], maxZoom: 12 });
            } else if (bounds.length === 1) {
                map.setView(bounds[0], zoom || 12);
            }
        }

        void boot();

        return () => {
            disposed = true;
            mapRef.current?.remove();
            mapRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pins, center, zoom, markerStyle, focusCenter, interactive]);

    return (
        <div
            ref={container}
            className={`z-0 w-full overflow-hidden rounded-2xl${onMapClick ? ' cursor-crosshair' : ''}`}
            style={{ height }}
        />
    );
}
