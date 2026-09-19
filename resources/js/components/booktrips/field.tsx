import type {
    InputHTMLAttributes,
    ReactNode,
    SelectHTMLAttributes,
    TextareaHTMLAttributes,
} from 'react';
import { cn } from '@/lib/utils';

export function Field({
    label,
    hint,
    children,
    className,
}: {
    label?: string;
    hint?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <label className={cn('mb-3 flex flex-col gap-1.5', className)}>
            {label ? (
                <span className="text-muted text-xs font-bold tracking-wide uppercase">
                    {label}
                </span>
            ) : null}
            {children}
            {hint ? (
                <em className="text-muted text-xs not-italic">{hint}</em>
            ) : null}
        </label>
    );
}

export function Input({
    className,
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return <input className={cn('input-base', className)} {...props} />;
}

export function Select({
    className,
    children,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement>) {
    return (
        <select
            className={cn('input-base cursor-pointer', className)}
            {...props}
        >
            {children}
        </select>
    );
}

export function Textarea({
    className,
    ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            className={cn('input-base min-h-[90px] resize-y', className)}
            {...props}
        />
    );
}
