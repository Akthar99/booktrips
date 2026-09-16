import { Link, router, usePage } from '@inertiajs/react';
import { send as resendVerification } from '@/actions/App/Http/Controllers/Auth/EmailVerificationController';
import Alert from '@/components/booktrips/alert';
import { withAuthLayout } from '@/layouts/app-layout';
import { useCountdown } from '@/lib/use-countdown';
import type { SharedProps } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type VerifyProps = {
    email: string | null;
    pendingEmail: string | null;
    verified: boolean;
};

const VerifyEmail: InertiaComponent<VerifyProps> = ({ email, pendingEmail }) => {
    const { auth, flash } = usePage<SharedProps>().props;
    const user = auth.user;
    const isVerified = Boolean(user?.email_verified);
    const resend = useCountdown();

    return (
        <div className="flex min-h-[calc(100vh-4.5rem)] items-center justify-center px-4 pt-8 pb-16">
            <div className="w-full max-w-[440px] rounded-[20px] border border-line bg-white p-7 text-center shadow-card">
                <h1 className="mb-2 text-3xl">{isVerified ? 'Email verified' : 'Verify your email'}</h1>
                {isVerified ? (
                    <p className="mb-5 text-muted">
                        {email || user?.email} is verified. You can book trips now.
                    </p>
                ) : (
                    <p className="mb-5 text-muted">
                        We sent a verification link to <strong>{email || user?.email}</strong>. Open it to unlock
                        booking. Booking needs a verified email, so request a new link if it went missing.
                    </p>
                )}
                {pendingEmail ? (
                    <Alert tone="note">
                        Waiting on confirmation for <strong>{pendingEmail}</strong>. Open the link we sent to
                        that address to finish the change.
                    </Alert>
                ) : null}
                {flash?.success ? <Alert tone="success">{flash.success}</Alert> : null}
                {flash?.error ? <Alert tone="error">{flash.error}</Alert> : null}
                {isVerified ? (
                    <Link
                        href="/search"
                        className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                    >
                        Browse packages
                    </Link>
                ) : user ? (
                    <button
                        type="button"
                        disabled={resend.active}
                        className="cursor-pointer rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:cursor-default disabled:opacity-60"
                        onClick={() => {
                            resend.start(60);
                            router.post(resendVerification.url(), {}, { preserveScroll: true });
                        }}
                    >
                        {resend.active ? `Resend in ${resend.seconds}s` : 'Resend verification email'}
                    </button>
                ) : (
                    <Link
                        href="/login"
                        className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                    >
                        Log in to resend
                    </Link>
                )}
            </div>
        </div>
    );
};

VerifyEmail.layout = withAuthLayout;

export default VerifyEmail;
