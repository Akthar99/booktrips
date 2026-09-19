import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

/**
 * Shared shell for the static legal pages (privacy policy, terms of service).
 */
export default function LegalShell({
    title,
    updated,
    intro,
    children,
}: {
    title: string;
    updated: string;
    intro: string;
    children: ReactNode;
}) {
    return (
        <div className="mx-auto w-[min(820px,calc(100%-2rem))] py-8 pb-16">
            <Head title={title} />
            <p className="text-brand-800 text-[13px] font-bold tracking-[0.12em] uppercase">
                Legal
            </p>
            <h1 className="mt-2 text-4xl">{title}</h1>
            <p className="text-muted mt-2 text-sm">
                Caymass Holidays Pvt Ltd · Last updated {updated}
            </p>
            <p className="mt-4 text-[15px] text-[#3f3f46]">{intro}</p>
            <div className="mt-7 space-y-7">{children}</div>
        </div>
    );
}

/**
 * One numbered/headed block of a legal page.
 */
export function LegalSection({
    heading,
    children,
}: {
    heading: string;
    children: ReactNode;
}) {
    return (
        <section>
            <h2 className="text-xl">{heading}</h2>
            <div className="mt-2 space-y-2 text-[15px] leading-relaxed text-[#3f3f46]">
                {children}
            </div>
        </section>
    );
}
