import {
    Binoculars,
    Compass,
    Flower,
    Home,
    Hotel,
    Landmark,
    Mountain,
    Sailboat,
    Sun,
    Tent,
    Waves,
    Zap,
    type LucideIcon,
} from 'lucide-react';

export type PackageLike = {
    duration_days?: number | null;
    duration_nights?: number | null;
    price_type?: string | null;
};

export const lkr = (n?: number | null): string =>
    'Rs. ' + Number(n || 0).toLocaleString('en-LK');

export function durationLabel(pkg?: PackageLike | null): string {
    if (!pkg) {
        return '';
    }

    const days = pkg.duration_days || 1;
    const nights = pkg.duration_nights || 0;

    if (days <= 1 && nights === 0) {
        return 'Day out';
    }

    if (nights === 1) {
        return '1 night';
    }

    if (nights > 1) {
        return `${nights} nights`;
    }

    return `${days} days`;
}

export function priceHint(pkg?: PackageLike | null): string {
    const type = pkg?.price_type || 'per_package';

    if (type === 'per_person') {
        return 'per person';
    }

    if (type === 'per_night') {
        return 'per night';
    }

    return 'per package';
}

export function ratingWord(rating?: number | null): string {
    const r = Number(rating || 0);

    if (r >= 4.8) {
        return 'Exceptional';
    }

    if (r >= 4.5) {
        return 'Excellent';
    }

    if (r >= 4) {
        return 'Very good';
    }

    if (r >= 3) {
        return 'Good';
    }

    return 'Pleasant';
}

export const CATEGORY_LABELS: Record<string, string> = {
    camping: 'Camping',
    dayout: 'Day out',
    activities: 'Activities',
    hotels: 'Hotels',
    villas: 'Villas',
    hiking: 'Hiking',
    beach: 'Beach',
    wildlife: 'Wildlife',
    adventure: 'Adventure',
    cultural: 'Cultural',
    watersports: 'Water sports',
    wellness: 'Wellness',
};

// Keyed by the `icon` name sent with each category (see config/booktrips.php).
export const CATEGORY_ICONS: Record<string, LucideIcon> = {
    tent: Tent,
    sun: Sun,
    compass: Compass,
    hotel: Hotel,
    home: Home,
    mountain: Mountain,
    waves: Waves,
    binoculars: Binoculars,
    zap: Zap,
    landmark: Landmark,
    sailboat: Sailboat,
    flower: Flower,
};

export const WEEKDAYS = [
    { n: 0, label: 'Sun' },
    { n: 1, label: 'Mon' },
    { n: 2, label: 'Tue' },
    { n: 3, label: 'Wed' },
    { n: 4, label: 'Thu' },
    { n: 5, label: 'Fri' },
    { n: 6, label: 'Sat' },
];

export const MONTHS = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

export function formatDay(iso?: string | null): string {
    if (!iso) {
        return '—';
    }

    const [y, m, d] = iso.slice(0, 10).split('-').map(Number);
    const date = new Date(y, (m || 1) - 1, d || 1);

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatDateTime(iso?: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function todayIso(): string {
    const now = new Date();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${m}-${d}`;
}
