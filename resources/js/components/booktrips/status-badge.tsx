import { cn } from '@/lib/utils';

const TONES: Record<string, string> = {
    requested: 'bg-orange-50 text-warn',
    confirmed: 'bg-brand-100 text-brand-950',
    completed: 'bg-sky-100 text-navy-800',
    rejected: 'bg-red-100 text-danger',
    cancelled: 'bg-red-100 text-danger',
    open: 'bg-brand-50 text-brand-950',
    submitted: 'bg-brand-50 text-brand-950',
    paid: 'bg-sky-100 text-navy-800',
    pending: 'bg-orange-50 text-warn',
    active: 'bg-brand-100 text-brand-950',
    suspended: 'bg-red-100 text-danger',
    approved: 'bg-brand-100 text-brand-950',
};

export default function StatusBadge({
    status,
    className,
}: {
    status: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-block rounded-full px-2 py-1 text-[11px] font-extrabold tracking-wide uppercase',
                TONES[status] ?? 'bg-cream-dark text-muted',
                className,
            )}
        >
            {status}
        </span>
    );
}
