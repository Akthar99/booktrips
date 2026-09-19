import { Link } from '@inertiajs/react';
import Logo from '@/components/booktrips/logo';

const COLUMNS: Array<{ heading: string; links: Array<[string, string]> }> = [
    {
        heading: 'Explore',
        links: [
            ['Map', '/map'],
            ['Camping', '/search?category=camping'],
            ['Day outs', '/search?category=dayout'],
            ['Hotels', '/search?category=hotels'],
            ['Villas', '/search?category=villas'],
            ['Wildlife', '/search?category=wildlife'],
        ],
    },
    {
        heading: 'Partners',
        links: [
            ['List your package', '/partners'],
            ['Request to join', '/partners/apply'],
            ['How BookTrips works', '/about'],
        ],
    },
    {
        heading: 'Account',
        links: [
            ['Log in', '/login'],
            ['Sign up', '/register'],
            ['My bookings', '/account/bookings'],
            ['Help & support', '/support'],
        ],
    },
];

export default function Footer() {
    return (
        <footer className="bg-navy-950 pt-12 pb-6 text-sm text-[#9fb0c3]">
            <div className="mx-auto mb-7 grid w-[min(1180px,calc(100%-2rem))] gap-6 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div>
                    <Logo light />
                    <p className="mt-3 max-w-80">
                        Day outs, camping nights, villas and activity packages
                        across Sri Lanka. Pay when you arrive.
                    </p>
                </div>
                {COLUMNS.map((column) => (
                    <div key={column.heading}>
                        <h4 className="mb-3 font-sans text-white">
                            {column.heading}
                        </h4>
                        {column.links.map(([label, href]) => (
                            <Link
                                key={href + label}
                                href={href}
                                className="block py-1 hover:text-white"
                            >
                                {label}
                            </Link>
                        ))}
                    </div>
                ))}
            </div>
            <div className="border-navy-700 mx-auto flex w-[min(1180px,calc(100%-2rem))] flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t pt-4">
                <span>
                    © {new Date().getFullYear()} BookTrips. Made for exploring
                    Sri Lanka.
                </span>
                <span className="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <Link href="/privacy" className="hover:text-white">
                        Privacy Policy
                    </Link>
                    <Link href="/terms" className="hover:text-white">
                        Terms of Service
                    </Link>
                    <span>Pay at destination only</span>
                </span>
            </div>
        </footer>
    );
}
