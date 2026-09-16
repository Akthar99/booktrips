import { Link } from '@inertiajs/react';
import { withAppLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

type ErrorProps = { status: number };

const ErrorPage: InertiaComponent<ErrorProps> = ({ status }) => {
    const title =
        {
            503: '503: Service unavailable',
            500: '500: Server error',
            404: '404: Page not found',
            403: '403: Forbidden',
        }[status] ?? 'Something went wrong';

    const description =
        {
            503: 'Sorry, we are doing some maintenance. Please check back soon.',
            500: 'Whoops, something went wrong on our servers.',
            404: 'Sorry, the page you are looking for could not be found.',
            403: 'Sorry, you are not allowed to view this page.',
        }[status] ?? 'Please try again in a moment.';

    return (
        <div className="flex min-h-[60vh] items-center justify-center px-4 py-16">
            <div className="w-full max-w-[520px] rounded-[20px] border border-line bg-white p-8 text-center shadow-card">
                <h1 className="mb-2 text-3xl">{title}</h1>
                <p className="mb-5 text-muted">{description}</p>
                <Link
                    href="/"
                    className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                >
                    Back home
                </Link>
            </div>
        </div>
    );
};

ErrorPage.layout = withAppLayout;

export default ErrorPage;
