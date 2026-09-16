import { Link, useForm } from '@inertiajs/react';
import { store as passwordEmail } from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import Alert from '@/components/booktrips/alert';
import { Field, Input } from '@/components/booktrips/field';
import { withAuthLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

type ForgotProps = { status?: string | null };

const ForgotPassword: InertiaComponent<ForgotProps> = () => {
    const form = useForm({ email: '' });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(passwordEmail.url());
    }

    return (
        <div className="flex min-h-[calc(100vh-4.5rem)] items-center justify-center px-4 pt-8 pb-16">
            <form
                onSubmit={submit}
                className="w-full max-w-[440px] rounded-[20px] border border-line bg-white p-7 shadow-card"
                noValidate
            >
                <h1 className="mb-2 text-3xl">Forgot password</h1>
                <p className="mb-5 text-muted">
                    Enter your email and we’ll send a link to choose a new password.
                </p>
                {form.wasSuccessful && form.recentlySuccessful ? (
                    <Alert tone="success">If that email is registered, we sent a reset link.</Alert>
                ) : null}
                {form.errors.email ? <Alert tone="error">{form.errors.email}</Alert> : null}
                <Field label="Email">
                    <Input
                        type="email"
                        required
                        value={form.data.email}
                        onChange={(event) => form.setData('email', event.target.value)}
                    />
                </Field>
                <button
                    type="submit"
                    className="w-full cursor-pointer rounded-full bg-brand-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:opacity-55"
                    disabled={form.processing}
                >
                    {form.processing ? 'Sending…' : 'Send reset link'}
                </button>
                <p className="mt-4 text-sm">
                    <Link className="font-bold text-brand-800" href="/login">
                        Back to log in
                    </Link>
                </p>
            </form>
        </div>
    );
};

ForgotPassword.layout = withAuthLayout;

export default ForgotPassword;
