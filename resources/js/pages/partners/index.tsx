import { Link } from '@inertiajs/react';
import { withAppLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

const PERKS = [
    {
        title: 'Reach weekend travellers',
        text: 'People already searching for Ella camps, Yala jeeps and south-coast villas. Your package sits next to those searches.',
    },
    {
        title: 'You collect the money',
        text: 'Guests pay at the destination. BookTrips never takes a card. We invoice 10% of finished bookings at month end.',
    },
    {
        title: 'Confirm what you can host',
        text: 'Every request lands as pending. Confirm or reject. No surprise arrivals.',
    },
    {
        title: 'One panel for stays and days',
        text: 'Hotels, villas, camps and activity operators use the same tools: packages, bookings, receipts.',
    },
];

const Partners: InertiaComponent<{
    businessTypes: Array<{ slug: string; name: string }>;
}> = () => {
    return (
        <>
            <section className="relative flex min-h-[420px] items-end overflow-hidden py-10 pb-14 text-white">
                <div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{
                        backgroundImage:
                            'linear-gradient(180deg, rgba(11,29,54,.35), rgba(11,29,54,.78)), url(https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1800&q=80)',
                    }}
                />
                <div className="relative z-10 mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <div className="text-brand-500 mb-3 text-[13px] font-bold tracking-[0.12em] uppercase">
                        Hotels, villas, camps &amp; operators
                    </div>
                    <h1 className="mb-3 max-w-[16ch] text-4xl text-white md:text-5xl">
                        List packages. Confirm requests. Collect on site.
                    </h1>
                    <p className="mb-7 max-w-[46ch] text-[17px] text-[#e8efe9]">
                        Our team reviews your application. After approval you
                        get a panel for offers, bookings and monthly 10% bills.
                    </p>
                    <Link
                        href="/partners/apply"
                        className="bg-brand-800 hover:bg-brand-900 inline-flex rounded-full px-4.5 py-2.5 text-sm font-bold text-white transition"
                    >
                        Request to join
                    </Link>
                </div>
            </section>

            <section className="py-14">
                <div className="mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <h2 className="mb-5 text-3xl">
                        Why partners use BookTrips
                    </h2>
                    <div className="grid gap-4.5 md:grid-cols-3">
                        {PERKS.map((perk, index) => (
                            <div
                                key={perk.title}
                                className="border-line rounded-2xl border bg-white p-5.5"
                            >
                                <span className="text-brand-800 mb-2 block font-extrabold">
                                    0{index + 1}
                                </span>
                                <h3 className="mb-1.5 font-sans text-lg">
                                    {perk.title}
                                </h3>
                                <p className="text-muted">{perk.text}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="bg-navy-900 py-16 text-[#e8efe9]">
                <div className="mx-auto w-[min(1180px,calc(100%-2rem))]">
                    <h2 className="mb-2.5 text-3xl text-white">
                        How joining works
                    </h2>
                    <div className="mt-7 grid gap-4.5 md:grid-cols-3">
                        {[
                            [
                                '01',
                                'Apply',
                                'Business details and social links. We need to see you are real.',
                            ],
                            [
                                '02',
                                'Wait for approval',
                                'Our team confirms the request. Then the panel unlocks.',
                            ],
                            [
                                '03',
                                'Host & settle 10%',
                                'Confirm bookings. After a stay is marked finished, 10% goes on your monthly bill — pay by bank transfer and upload the receipt.',
                            ],
                        ].map(([step, title, copy]) => (
                            <div
                                key={step}
                                className="bg-navy-800 rounded-2xl p-5.5"
                            >
                                <span className="text-brand-500 mb-2 block font-extrabold">
                                    {step}
                                </span>
                                <h3 className="mb-1.5 font-sans text-lg text-white">
                                    {title}
                                </h3>
                                <p className="text-[#b7c4d1]">{copy}</p>
                            </div>
                        ))}
                    </div>
                    <div className="mt-7">
                        <Link
                            href="/partners/apply"
                            className="bg-brand-800 hover:bg-brand-900 inline-flex rounded-full px-4.5 py-2.5 text-sm font-bold text-white transition"
                        >
                            Request to join
                        </Link>
                    </div>
                </div>
            </section>
        </>
    );
};

Partners.layout = withAppLayout;

export default Partners;
