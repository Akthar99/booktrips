import { useEffect, useRef, useState } from 'react';
import { Check, Copy, Share2 } from 'lucide-react';

/**
 * Copy / WhatsApp / Facebook sharing for a public package link.
 */
export default function SharePackage({
    slug,
    id,
    title,
    label = 'Share',
    className,
}: {
    slug?: string | null;
    id: number;
    title: string;
    label?: string;
    className?: string;
}) {
    const [open, setOpen] = useState(false);
    const [copied, setCopied] = useState(false);
    const wrap = useRef<HTMLDivElement>(null);

    const path = `/packages/${slug || id}`;
    const url =
        typeof window === 'undefined'
            ? path
            : `${window.location.origin}${path}`;
    const text = `${title} · BookTrips.lk`;

    useEffect(() => {
        function onDoc(event: MouseEvent) {
            if (wrap.current && !wrap.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', onDoc);

        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    async function copy() {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            setTimeout(() => setCopied(false), 1800);
        } catch {
            window.prompt('Copy this link', url);
        }
    }

    async function nativeShare() {
        if (navigator.share) {
            try {
                await navigator.share({ title, text, url });
                setOpen(false);

                return;
            } catch {
                // fall through to the popover when the user cancels or the browser refuses
            }
        }

        setOpen((value) => !value);
    }

    return (
        <div className="relative inline-block" ref={wrap}>
            <button
                type="button"
                className={
                    className ??
                    'border-line text-brand-900 hover:border-brand-700 inline-flex cursor-pointer items-center gap-1.5 rounded-full border bg-white px-3 py-1.5 text-[13px] font-bold transition'
                }
                onClick={nativeShare}
                aria-haspopup="dialog"
                aria-expanded={open}
            >
                <Share2 size={14} />
                {label}
            </button>
            {open ? (
                <div className="border-line shadow-card absolute top-[calc(100%+6px)] right-0 z-90 w-72 rounded-[14px] border bg-white p-3 text-left">
                    <span className="text-muted mb-2 block text-xs font-bold tracking-wide uppercase">
                        Share this package
                    </span>
                    <div className="mb-2 flex items-center gap-2">
                        <input
                            readOnly
                            value={url}
                            className="input-base min-w-0 flex-1 text-[12px]"
                            onFocus={(event) => event.target.select()}
                        />
                        <button
                            type="button"
                            className="border-line text-brand-900 grid h-9.5 w-9.5 shrink-0 cursor-pointer place-items-center rounded-lg border bg-white"
                            onClick={copy}
                            aria-label="Copy link"
                        >
                            {copied ? <Check size={15} /> : <Copy size={15} />}
                        </button>
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                        <a
                            href={`https://wa.me/?text=${encodeURIComponent(`${text} ${url}`)}`}
                            target="_blank"
                            rel="noreferrer"
                            className="bg-brand-50 text-brand-900 rounded-lg px-3 py-2 text-center text-[13px] font-bold"
                        >
                            WhatsApp
                        </a>
                        <a
                            href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`}
                            target="_blank"
                            rel="noreferrer"
                            className="bg-brand-50 text-brand-900 rounded-lg px-3 py-2 text-center text-[13px] font-bold"
                        >
                            Facebook
                        </a>
                    </div>
                    {copied ? (
                        <span className="text-brand-800 mt-2 block text-xs font-bold">
                            Link copied
                        </span>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
