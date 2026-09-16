import { Link, useForm } from '@inertiajs/react';
import { store as passwordStore } from '@/actions/App/Http/Controllers/Auth/NewPasswordController';
import Alert from '@/components/booktrips/alert';
import { Field, Input } from '@/components/booktrips/field';
import PasswordRules from '@/components/booktrips/password-rules';
import { withAuthLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

type ResetProps = { token: string; email: string };

const ResetPassword: InertiaComponent<ResetProps> = ({ token, email }) => {
    const form = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(passwordStore.url());
    }

    return (
        <div className="flex min-h-[calc(100vh-4.5rem)] items-center justify-center px-4 pt-8 pb-16">
            <form
                onSubmit={submit}
                className="w-full max-w-[440px] rounded-[20px] border border-line bg-white p-7 shadow-card"
                noValidate
            >
                <h1 className="mb-2 text-3xl">Choose a new password</h1>
                <p className="mb-5 text-muted">Use a strong password you have not used here before.</p>
                {form.errors.email ? <Alert tone="error">{form.errors.email}</Alert> : null}
                {form.errors.password ? <Alert tone="error">{form.errors.password}</Alert> : null}
                <Field label="Email">
                    <Input
                        type="email"
                        required
                        value={form.data.email}
                        onChange={(event) => form.setData('email', event.target.value)}
                    />
                </Field>
                <Field label="New password">
                    <Input
                        type="password"
                        required
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(event) => form.setData('password', event.target.value)}
                    />
                </Field>
                <PasswordRules password={form.data.password} />
                <Field label="Confirm new password">
                    <Input
                        type="password"
                        required
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(event) => form.setData('password_confirmation', event.target.value)}
                    />
                </Field>
                <button
                    type="submit"
                    className="w-full cursor-pointer rounded-full bg-brand-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:opacity-55"
                    disabled={form.processing}
                >
                    {form.processing ? 'Updating…' : 'Update password'}
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

ResetPassword.layout = withAuthLayout;

export default ResetPassword;
