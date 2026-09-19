import { Link } from '@inertiajs/react';
import AdBanner from '@/components/booktrips/ad-banner';
import { withAppLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

const About: InertiaComponent<Record<string, never>> = () => {
    return (
        <div className="mx-auto w-[min(760px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">How BookTrips works</h1>
            <p className="text-muted mt-3 mb-5">
                BookTrips is for Sri Lankan weekends: a day out from Colombo, a
                camping night in Ella, a villa for the family, a jeep at Yala.
                Hosts list packages. You reserve with a verified email. You pay
                the host when you arrive — cash or whatever they accept on site.
            </p>
            <h3 className="text-xl">Travellers</h3>
            <p className="text-[15px] text-[#3f3f46]">
                Create an account, verify the email we send, then reserve. Your
                booking code is the only thing the host needs.
            </p>
            <h3 className="mt-5 text-xl">Hotels, villas &amp; operators</h3>
            <p className="text-[15px] text-[#3f3f46]">
                Register the business, add a package with photos, price and
                what’s included. Confirmed bookings show up on your dashboard.
            </p>
            <p className="mt-6">
                <Link
                    href="/search"
                    className="bg-brand-800 hover:bg-brand-900 inline-flex rounded-full px-4.5 py-2.5 text-sm font-bold text-white transition"
                >
                    Explore packages
                </Link>
            </p>
            <div className="mt-9">
                <AdBanner slot="rectangle" />
            </div>
        </div>
    );
};

About.layout = withAppLayout;

export default About;
