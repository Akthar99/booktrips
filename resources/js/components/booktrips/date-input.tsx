import { useEffect, useMemo, useRef, useState } from 'react';
import { MONTHS } from '@/lib/booktrips';
import { cn } from '@/lib/utils';

const WEEK = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

/** Popup width in px: wide enough for comfortable day cells on every layout. */
const POPUP_WIDTH = 272;

function parseIso(value?: string | null): Date | null {
    if (!value) {
        return null;
    }

    const [y, m, d] = String(value).slice(0, 10).split('-').map(Number);

    if (!y || !m || !d) {
        return null;
    }

    return new Date(y, m - 1, d);
}

function toIso(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function startOfDay(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function formatDisplay(iso?: string | null): string {
    const date = parseIso(iso);

    if (!date) {
        return '';
    }

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function daysInGrid(year: number, month: number): Array<Date | null> {
    const first = new Date(year, month, 1);
    const offset = first.getDay();
    const count = new Date(year, month + 1, 0).getDate();
    const cells: Array<Date | null> = [];

    for (let i = 0; i < offset; i += 1) {
        cells.push(null);
    }

    for (let d = 1; d <= count; d += 1) {
        cells.push(new Date(year, month, d));
    }

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    return cells;
}

export default function DateInput({
    label,
    value,
    onChange,
    min,
    required = false,
    bare = false,
    className,
}: {
    label?: string;
    value: string;
    onChange: (value: string) => void;
    min?: string;
    required?: boolean;
    bare?: boolean;
    className?: string;
}) {
    const wrap = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const [alignRight, setAlignRight] = useState(false);
    const selected = parseIso(value);
    const minDate = parseIso(min);
    const [cursor, setCursor] = useState(() => {
        const base = selected || new Date();

        return { y: base.getFullYear(), m: base.getMonth() };
    });

    useEffect(() => {
        if (!open) {
            return;
        }

        const base = selected || new Date();
        setCursor({ y: base.getFullYear(), m: base.getMonth() });
    }, [open, value]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        function onDoc(event: MouseEvent) {
            if (wrap.current && !wrap.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', onDoc);

        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const cells = useMemo(() => daysInGrid(cursor.y, cursor.m), [cursor]);
    const today = startOfDay(new Date());

    function pick(date: Date) {
        if (minDate && startOfDay(date) < startOfDay(minDate)) {
            return;
        }

        onChange(toIso(date));
        setOpen(false);
    }

    /**
     * Open the calendar, flipping it to the left when it would run off screen.
     */
    function toggle() {
        const node = wrap.current;

        if (!open && node) {
            setAlignRight(
                node.getBoundingClientRect().left + POPUP_WIDTH >
                    window.innerWidth - 8,
            );
        }

        setOpen((value) => !value);
    }

    function shift(delta: number) {
        const next = new Date(cursor.y, cursor.m + delta, 1);
        setCursor({ y: next.getFullYear(), m: next.getMonth() });
    }

    return (
        <div
            className={cn('relative flex flex-col gap-1.5', className)}
            ref={wrap}
        >
            {label ? (
                <span className="text-muted text-xs font-bold tracking-wide uppercase">
                    {label}
                </span>
            ) : null}
            <button
                type="button"
                className={cn(
                    'border-line flex min-h-[43px] w-full cursor-pointer items-center rounded-xl border bg-white px-3 py-2.5 text-left text-sm font-semibold',
                    bare &&
                        'min-h-0 rounded-none border-0 bg-transparent p-0 text-[15px]',
                    !value && 'text-muted font-medium',
                )}
                onClick={toggle}
                aria-haspopup="dialog"
                aria-expanded={open}
            >
                {value ? formatDisplay(value) : 'Select date'}
            </button>
            {required ? (
                <input
                    type="text"
                    className="pointer-events-none absolute h-px w-px opacity-0"
                    value={value || ''}
                    onChange={() => undefined}
                    required
                    tabIndex={-1}
                    aria-hidden="true"
                />
            ) : null}
            {open ? (
                <div
                    className={cn(
                        'border-line shadow-card absolute top-[calc(100%+6px)] z-50 max-w-[calc(100vw-2rem)] rounded-[14px] border bg-white p-3',
                        alignRight ? 'right-0' : 'left-0',
                    )}
                    style={{ width: POPUP_WIDTH }}
                    role="dialog"
                    aria-label="Choose date"
                >
                    <div className="mb-2.5 flex items-center justify-between text-sm">
                        <button
                            type="button"
                            className="bg-brand-50 text-brand-900 h-8 w-8 cursor-pointer rounded-lg border-0 text-lg"
                            onClick={() => shift(-1)}
                            aria-label="Previous month"
                        >
                            ‹
                        </button>
                        <strong>
                            {MONTHS[cursor.m]} {cursor.y}
                        </strong>
                        <button
                            type="button"
                            className="bg-brand-50 text-brand-900 h-8 w-8 cursor-pointer rounded-lg border-0 text-lg"
                            onClick={() => shift(1)}
                            aria-label="Next month"
                        >
                            ›
                        </button>
                    </div>
                    <div className="grid grid-cols-7 gap-0.5 text-center">
                        {WEEK.map((w) => (
                            <em
                                key={w}
                                className="text-muted py-1.5 text-[11px] font-bold not-italic"
                            >
                                {w}
                            </em>
                        ))}
                        {cells.map((date, index) => {
                            if (!date) {
                                return <span key={`empty-${index}`} />;
                            }

                            const iso = toIso(date);
                            const disabled = Boolean(
                                minDate &&
                                startOfDay(date) < startOfDay(minDate),
                            );
                            const isSelected = value === iso;
                            const isToday =
                                startOfDay(date).getTime() === today.getTime();

                            return (
                                <button
                                    key={iso}
                                    type="button"
                                    disabled={disabled}
                                    className={cn(
                                        'text-ink hover:bg-brand-50 h-9 cursor-pointer rounded-lg border-0 text-[13px] font-semibold disabled:cursor-default disabled:opacity-30',
                                        isSelected &&
                                            'bg-brand-800 hover:bg-brand-800 text-white',
                                        isToday &&
                                            !isSelected &&
                                            'shadow-[inset_0_0_0_1px_var(--color-brand-700)]',
                                    )}
                                    onClick={() => pick(date)}
                                >
                                    {date.getDate()}
                                </button>
                            );
                        })}
                    </div>
                    <div className="mt-2 flex justify-between">
                        <button
                            type="button"
                            className="text-brand-800 cursor-pointer border-0 bg-transparent p-0 text-[13px] font-bold"
                            onClick={() => {
                                onChange('');
                                setOpen(false);
                            }}
                        >
                            Clear
                        </button>
                        <button
                            type="button"
                            className="text-brand-800 cursor-pointer border-0 bg-transparent p-0 text-[13px] font-bold"
                            onClick={() => pick(today)}
                        >
                            Today
                        </button>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
