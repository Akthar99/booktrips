import { useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';

/**
 * Adsterra banner slots.
 *
 * Keys come from the Adsterra dashboard — one key per banner size. Keep the
 * sizes in sync with the account configuration.
 */
export const AD_SLOTS = {
    rectangle: {
        key: '8f99ea719c343222874c1f835417229f',
        width: 300,
        height: 250,
    },
    skyscraper: {
        key: '2f49e68ca188cc61af87c54606a3b160',
        width: 160,
        height: 300,
    },
} as const;

export type AdSlot = keyof typeof AD_SLOTS;

declare global {
    interface Window {
        atOptions?: {
            key: string;
            format: string;
            height: number;
            width: number;
            params: Record<string, unknown>;
        };
    }
}

/**
 * invoke.js reads the shared window.atOptions global the moment it executes,
 * so two banners loading at once would both render whichever options were
 * written last. Every banner on the page therefore shares one queue: set the
 * options, load the script, and only then let the next banner start.
 */
let adQueue: Promise<void> = Promise.resolve();

export default function AdBanner({
    slot,
    className,
}: {
    slot: AdSlot;
    className?: string;
}) {
    const holder = useRef<HTMLDivElement>(null);
    const started = useRef(false);
    const { key, width, height } = AD_SLOTS[slot];

    useEffect(() => {
        const node = holder.current;

        if (!node || started.current) {
            return;
        }

        started.current = true;

        adQueue = adQueue.then(
            () =>
                new Promise<void>((resolve) => {
                    if (!node.isConnected) {
                        resolve();

                        return;
                    }

                    window.atOptions = {
                        key,
                        format: 'iframe',
                        height,
                        width,
                        params: {},
                    };

                    const script = document.createElement('script');
                    script.src = `https://www.highrevenueformat.com/${key}/invoke.js`;
                    script.async = true;
                    script.onload = () => resolve();
                    script.onerror = () => resolve();

                    node.appendChild(script);
                }),
        );
    }, [key, width, height]);

    return (
        <div
            ref={holder}
            role="complementary"
            aria-label="Advertisement"
            className={cn('mx-auto overflow-hidden', className)}
            style={{ width, height }}
        />
    );
}
