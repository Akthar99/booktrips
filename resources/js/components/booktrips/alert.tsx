import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Tone = 'error' | 'success' | 'warn' | 'note';

const TONES: Record<Tone, string> = {
    error: 'bg-red-50 text-danger',
    success: 'bg-brand-50 text-brand-950',
    warn: 'bg-orange-50 text-warn',
    note: 'bg-brand-50 text-brand-950',
};

export default function Alert({
    tone = 'note',
    children,
    className,
}: {
    tone?: Tone;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('my-2 rounded-xl px-3 py-2.5 text-[13px] leading-relaxed', TONES[tone], className)}>
            {children}
        </div>
    );
}
