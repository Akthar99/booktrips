import { Form, Link, usePage } from '@inertiajs/react';
import Alert from '@/components/booktrips/alert';
import { Field, Input } from '@/components/booktrips/field';
import { withAuthLayout } from '@/layouts/app-layout';
import type { SharedProps } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type LoginProps = { status?: string | null };

const Login: InertiaComponent<LoginProps> = ({ status }) => {
    const page = usePage<SharedProps>();
    const next =
        new URLSearchParams(page.url.split('?')[1] ?? '').get('next') ?? '';

    return (
        <div className="flex min-h-[calc(100vh-4.5rem)] items-center justify-center px-4 pt-8 pb-16">
            <Form
                action="/login"
                method="post"
                className="border-line shadow-card w-full max-w-[440px] rounded-[20px] border bg-white p-7"
            >
                {({ errors, processing }) => (
                    <>
                        <h1 className="mb-2 text-3xl">Log in</h1>
                        <p className="text-muted mb-5">
                            Reserve trips after your email is verified. Pay at
                            the destination.
                        </p>
                        {page.props.flash?.error ? (
                            <Alert tone="error">{page.props.flash.error}</Alert>
                        ) : null}
                        {status ? <Alert tone="success">{status}</Alert> : null}
                        {errors.email ? (
                            <Alert tone="error">{errors.email}</Alert>
                        ) : null}
                        <Field label="Email">
                            <Input
                                type="email"
                                name="email"
                                required
                                autoComplete="email"
                            />
                        </Field>
                        <Field label="Password">
                            <Input
                                type="password"
                                name="password"
                                required
                                autoComplete="current-password"
                            />
                        </Field>
                        {next ? (
                            <input type="hidden" name="next" value={next} />
                        ) : null}
                        <button
                            type="submit"
                            className="bg-brand-800 hover:bg-brand-900 w-full cursor-pointer rounded-full px-4 py-2.5 text-sm font-bold text-white transition disabled:opacity-55"
                            disabled={processing}
                        >
                            {processing ? 'Signing in…' : 'Log in'}
                        </button>
                        <p className="mt-3.5 flex gap-4 text-sm">
                            <Link
                                className="text-brand-800 font-bold"
                                href="/forgot-password"
                            >
                                Forgot password
                            </Link>
                        </p>
                        <p className="mt-4 text-sm">
                            No account?{' '}
                            <Link
                                className="text-brand-800 font-bold"
                                href="/register"
                            >
                                Sign up
                            </Link>
                        </p>
                    </>
                )}
            </Form>
        </div>
    );
};

Login.layout = withAuthLayout;

export default Login;
