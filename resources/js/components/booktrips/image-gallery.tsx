import { useCallback, useEffect, useRef, useState } from 'react';
import { ChevronLeft, ChevronRight, Expand, X } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Package photo strip with a full-screen viewer.
 *
 * The viewer supports click, keyboard (arrows/Escape) and horizontal swipe.
 */
export default function ImageGallery({
    images,
    title,
    fallback,
}: {
    images: string[];
    title: string;
    fallback: string;
}) {
    const gallery = images.length ? images : [fallback];
    const [open, setOpen] = useState(false);
    const [index, setIndex] = useState(0);
    const touchStart = useRef<number | null>(null);

    const go = useCallback(
        (delta: number) => {
            setIndex((current) => (current + delta + gallery.length) % gallery.length);
        },
        [gallery.length],
    );

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        function onKey(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            } else if (event.key === 'ArrowLeft') {
                go(-1);
            } else if (event.key === 'ArrowRight') {
                go(1);
            }
        }

        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKey);

        return () => {
            document.body.style.overflow = previous;
            window.removeEventListener('keydown', onKey);
        };
    }, [open, go]);

    function openAt(position: number) {
        setIndex(position);
        setOpen(true);
    }

    function onTouchStart(event: React.TouchEvent) {
        touchStart.current = event.touches[0]?.clientX ?? null;
    }

    function onTouchEnd(event: React.TouchEvent) {
        if (touchStart.current === null) {
            return;
        }

        const delta = (event.changedTouches[0]?.clientX ?? touchStart.current) - touchStart.current;
        touchStart.current = null;

        if (Math.abs(delta) > 45) {
            go(delta < 0 ? 1 : -1);
        }
    }

    return (
        <>
            <div className="relative grid min-h-[280px] cursor-pointer grid-cols-1 gap-2 overflow-hidden rounded-[20px] md:min-h-[420px] md:grid-cols-[2fr_1fr]">
                <button
                    type="button"
                    className="group relative block h-full w-full cursor-pointer border-0 bg-cream-dark p-0"
                    onClick={() => openAt(0)}
                    aria-label="Open photo viewer"
                >
                    <img src={gallery[0]} alt={title} className="h-full w-full object-cover" />
                </button>
                <div className="grid gap-2">
                    {[1, 2].map((position) => (
                        <button
                            key={position}
                            type="button"
                            className="relative block h-full w-full cursor-pointer border-0 bg-cream-dark p-0"
                            onClick={() => openAt(Math.min(position, gallery.length - 1))}
                            aria-label="Open photo viewer"
                        >
                            <img
                                src={gallery[position] || gallery[0]}
                                alt=""
                                className="h-full w-full object-cover"
                            />
                        </button>
                    ))}
                </div>
                <button
                    type="button"
                    className="absolute right-3 bottom-3 inline-flex cursor-pointer items-center gap-2 rounded-full bg-white/95 px-3.5 py-2 text-[13px] font-bold text-brand-900 shadow-card"
                    onClick={() => openAt(0)}
                >
                    <Expand size={15} />
                    {gallery.length} photo{gallery.length === 1 ? '' : 's'}
                </button>
            </div>

            {open ? (
                <div
                    className="fixed inset-0 z-120 flex flex-col bg-[#0b1d36]/96 backdrop-blur-sm"
                    role="dialog"
                    aria-modal="true"
                    aria-label={`${title} photos`}
                    onClick={() => setOpen(false)}
                    onTouchStart={onTouchStart}
                    onTouchEnd={onTouchEnd}
                >
                    <div className="flex items-center justify-between px-4 py-3 text-white">
                        <span className="text-sm font-semibold">
                            {index + 1} / {gallery.length}
                        </span>
                        <button
                            type="button"
                            className="grid h-10 w-10 cursor-pointer place-items-center rounded-full border-0 bg-white/10 text-white"
                            onClick={() => setOpen(false)}
                            aria-label="Close photo viewer"
                        >
                            <X size={18} />
                        </button>
                    </div>

                    <div className="relative flex min-h-0 flex-1 items-center justify-center px-2">
                        <button
                            type="button"
                            className="absolute left-3 z-10 grid h-11 w-11 cursor-pointer place-items-center rounded-full border-0 bg-white/15 text-white transition hover:bg-white/25"
                            onClick={(event) => {
                                event.stopPropagation();
                                go(-1);
                            }}
                            aria-label="Previous photo"
                        >
                            <ChevronLeft size={20} />
                        </button>
                        <img
                            src={gallery[index]}
                            alt={`${title} photo ${index + 1}`}
                            className="max-h-full max-w-full rounded-xl object-contain"
                            onClick={(event) => event.stopPropagation()}
                        />
                        <button
                            type="button"
                            className="absolute right-3 z-10 grid h-11 w-11 cursor-pointer place-items-center rounded-full border-0 bg-white/15 text-white transition hover:bg-white/25"
                            onClick={(event) => {
                                event.stopPropagation();
                                go(1);
                            }}
                            aria-label="Next photo"
                        >
                            <ChevronRight size={20} />
                        </button>
                    </div>

                    <div
                        className="flex justify-center gap-2 overflow-x-auto px-4 py-4"
                        onClick={(event) => event.stopPropagation()}
                    >
                        {gallery.map((image, position) => (
                            <button
                                key={`${image}-${position}`}
                                type="button"
                                className={cn(
                                    'h-14 w-20 shrink-0 cursor-pointer overflow-hidden rounded-lg border-2 border-transparent p-0',
                                    position === index && 'border-brand-500',
                                )}
                                onClick={() => setIndex(position)}
                                aria-label={`Show photo ${position + 1}`}
                            >
                                <img src={image} alt="" className="h-full w-full object-cover" />
                            </button>
                        ))}
                    </div>
                </div>
            ) : null}
        </>
    );
}
