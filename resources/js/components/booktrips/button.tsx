import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type Variant = 'primary' | 'dark' | 'ghost' | 'outline' | 'danger';
type Size = 'md' | 'sm';

const VARIANTS: Record<Variant, string> = {
    primary: 'bg-brand-800 text-white hover:bg-brand-900',
    dark: 'bg-navy-900 text-white hover:bg-navy-950',
    ghost: 'bg-transparent text-brand-900 hover:bg-brand-50',
    outline: 'border border-line bg-white text-brand-900 hover:border-brand-700',
    danger: 'border border-red-200 bg-white text-danger hover:border-red-400',
};

const SIZES: Record<Size, string> = {
    md: 'px-4.5 py-2.5 text-sm',
    sm: 'px-3 py-1.5 text-[13px]',
};

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: Variant;
    size?: Size;
    block?: boolean;
};

export default function Button({
    variant = 'primary',
    size = 'md',
    block = false,
    className,
    type = 'button',
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            className={cn(
                'inline-flex cursor-pointer items-center justify-center gap-2 rounded-full font-bold transition disabled:cursor-not-allowed disabled:opacity-55',
                VARIANTS[variant],
                SIZES[size],
                block && 'w-full',
                className,
            )}
            {...props}
        />
    );
}
