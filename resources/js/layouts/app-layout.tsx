import { useEffect, useState, type ReactNode } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import Footer from '@/components/booktrips/footer';
import Header from '@/components/booktrips/header';
import type { SharedProps } from '@/types/booktrips';

export function Container({
    children,
    wide = false,
    className,
}: {
    children: ReactNode;
    wide?: boolean;
    className?: string;
}) {
    return (
        <div
            className={
                'mx-auto ' +
                (wide
                    ? 'w-[min(1280px,calc(100%-2rem))]'
                    : 'w-[min(1180px,calc(100%-2rem))]') +
                (className ? ` ${className}` : '')
            }
        >
            {children}
        </div>
    );
}

function FlashToasts() {
    const { flash } = usePage<SharedProps>().props;
    const [visible, setVisible] = useState(true);
    const message = flash?.success || flash?.error || flash?.status;
    const tone = flash?.error ? 'error' : 'success';

    useEffect(() => {
        setVisible(true);

        if (!message) {
            return undefined;
        }

        const timer = setTimeout(() => setVisible(false), 6000);

        return () => clearTimeout(timer);
    }, [message]);

    if (!message || !visible) {
        return null;
    }

    return (
        <div className="fixed top-20 right-4 z-[110] max-w-sm">
            <div
                className={
                    'shadow-card flex items-start gap-3 rounded-xl border px-4 py-3 text-sm ' +
                    (tone === 'error'
                        ? 'text-danger border-red-200 bg-red-50'
                        : 'border-brand-100 text-brand-950 bg-white')
                }
                role="status"
            >
                <span>{message}</span>
                <button
                    type="button"
                    className="text-muted ml-auto cursor-pointer border-0 bg-transparent p-0"
                    onClick={() => setVisible(false)}
                    aria-label="Dismiss"
                >
                    <X size={15} />
                </button>
            </div>
        </div>
    );
}

export default function AppLayout({
    children,
    title,
    hideFooter = false,
}: {
    children: ReactNode;
    title?: string;
    hideFooter?: boolean;
}) {
    return (
        <div className="flex min-h-screen flex-col">
            <Head title={title} />
            <Header />
            <main className="flex-1">{children}</main>
            {hideFooter ? null : <Footer />}
            <FlashToasts />
        </div>
    );
}

/**
 * Attach the standard header/footer shell to an Inertia page.
 */
export const withAppLayout = (page: ReactNode): ReactNode => (
    <AppLayout>{page}</AppLayout>
);

/**
 * Same shell without the footer (auth screens keep the page compact).
 */
export const withAuthLayout = (page: ReactNode): ReactNode => (
    <AppLayout hideFooter>{page}</AppLayout>
);
