import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export type TabItem = { label: string; href: string; exact?: boolean };

export default function Tabs({ items }: { items: TabItem[] }) {
    const { url } = usePage();

    return (
        <nav className="my-4 mb-5 flex flex-wrap gap-2">
            {items.map((item) => {
                const path = url.split('?')[0];
                const active = item.exact
                    ? path === item.href
                    : path.startsWith(item.href);

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                            'border-line text-ink rounded-full border bg-white px-3 py-2 text-sm font-bold transition',
                            active &&
                                'border-brand-800 bg-brand-800 text-white',
                        )}
                    >
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}
