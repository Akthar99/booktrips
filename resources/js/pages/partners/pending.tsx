import { Link, router } from '@inertiajs/react';
import { send as resendVerification } from '@/actions/App/Http/Controllers/Auth/EmailVerificationController';
import Alert from '@/components/booktrips/alert';
import { withAuthLayout } from '@/layouts/app-layout';
import { useCountdown } from '@/lib/use-countdown';
import type { InertiaComponent } from '@/types/inertia';

type PendingProps = {
    business: { name: string; city: string; type: string; approved: boolean } | null;
    emailVerified: boolean;
};

const Pending: InertiaComponent<PendingProps> = ({ business, emailVerified }) => {
    const approved = Boolean(business?.approved);
    const resend = useCountdown();

    return (
        <div className="flex min-h-[calc(100vh-4.5rem)] items-center justify-center px-4 pt-8 pb-16">
            <div className="w-full max-w-[440px] rounded-[20px] border border-line bg-white p-7 text-center shadow-card">
                {approved ? (
                    <>
                        <h1 className="mb-2 text-3xl">You’re approved</h1>
                        <p className="mb-5 text-muted">Your partner panel is open.</p>
                        <Link
                            href="/partners/dashboard"
                            className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                        >
                            Open dashboard
                        </Link>
                    </>
                ) : (
                    <>
                        <h1 className="mb-2 text-3xl">Request received</h1>
                        <p className="mb-5 text-muted">
                            {business?.name || 'Your business'} is waiting for our team to confirm. You’ll get the
                            panel for packages, bookings and payments after that.
                        </p>
                        {!emailVerified ? (
                            <>
                                <Alert tone="warn">
                                    We still need to verify your email. Confirm it so booking notifications and
                                    approval news reach you.
                                </Alert>
                                <button
                                    type="button"
                                    disabled={resend.active}
                                    className="mb-3 cursor-pointer rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:cursor-default disabled:opacity-60"
                                    onClick={() => {
                                        resend.start(60);
                                        router.post(resendVerification.url(), {}, { preserveScroll: true });
                                    }}
                                >
                                    {resend.active ? `Resend in ${resend.seconds}s` : 'Resend verification email'}
                                </button>
                            </>
                        ) : null}
                        <Link
                            href="/"
                            className="inline-flex rounded-full border border-line bg-white px-4.5 py-2.5 text-sm font-bold text-brand-900 transition hover:border-brand-700"
                        >
                            Back home
                        </Link>
                    </>
                )}
            </div>
        </div>
    );
};

Pending.layout = withAuthLayout;

export default Pending;
