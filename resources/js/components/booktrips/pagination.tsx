import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export default function Pagination({
    page,
    lastPage,
    total,
}: {
    page: number;
    lastPage: number;
    total: number;
}) {
    const { url } = usePage();
    const [path, queryString] = url.split('?');

    function hrefFor(target: number): string {
        const query = new URLSearchParams(queryString || '');

        if (target <= 1) {
            query.delete('page');
        } else {
            query.set('page', String(target));
        }

        const suffix = query.toString();

        return suffix ? `${path}?${suffix}` : path;
    }

    if (lastPage <= 1) {
        return (
            <p className="text-muted my-4 text-[13px]">
                {total} result{total === 1 ? '' : 's'}
            </p>
        );
    }

    const linkClass =
        'rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold text-brand-900 transition hover:border-brand-700';
    const disabledClass = 'pointer-events-none opacity-40';

    return (
        <div className="my-4 flex items-center justify-between gap-3">
            <span className="text-muted text-[13px]">
                Page {page} of {lastPage} · {total} results
            </span>
            <div className="flex gap-2">
                <Link
                    href={hrefFor(page - 1)}
                    className={cn(linkClass, page <= 1 && disabledClass)}
                    preserveScroll
                >
                    Previous
                </Link>
                <Link
                    href={hrefFor(page + 1)}
                    className={cn(linkClass, page >= lastPage && disabledClass)}
                    preserveScroll
                >
                    Next
                </Link>
            </div>
        </div>
    );
}
