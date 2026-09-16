import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { store as registerRoute } from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import Alert from '@/components/booktrips/alert';
import { Field, Input } from '@/components/booktrips/field';
import PasswordRules from '@/components/booktrips/password-rules';
import { withAuthLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

type RegisterProps = Record<string, never>;

const Register: InertiaComponent<RegisterProps> = () => {
    const [step, setStep] = useState(1);
    const [stepError, setStepError] = useState('');

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    function continueToPassword(event: React.FormEvent) {
        event.preventDefault();
        setStepError('');

        if (!form.data.name.trim() || !form.data.email.trim()) {
            setStepError('Name and email are required.');

            return;
        }

        setStep(2);
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(registerRoute.url(), {
            onError: () => setStep(2),
        });
    }

    return (
        <div className="flex min-h-[calc(100vh-4.5rem)] items-center justify-center px-4 pt-8 pb-16">
            <form
                onSubmit={step === 1 ? continueToPassword : submit}
                className="w-full max-w-[440px] rounded-[20px] border border-line bg-white p-7 shadow-card"
                noValidate
            >
                <p className="mb-2 text-[13px] font-bold tracking-[0.12em] text-brand-800 uppercase">
                    Step {step} of 2
                </p>
                <h1 className="mb-2 text-3xl">{step === 1 ? 'Your details' : 'Set a password'}</h1>
                <p className="mb-5 text-muted">
                    {step === 1
                        ? 'We’ll send a verification link to this email.'
                        : 'Use a strong password. You’ll need it to book.'}
                </p>
                {stepError ? <Alert tone="error">{stepError}</Alert> : null}
                {form.errors.name ? <Alert tone="error">{form.errors.name}</Alert> : null}
                {form.errors.email ? <Alert tone="error">{form.errors.email}</Alert> : null}
                {form.errors.password ? <Alert tone="error">{form.errors.password}</Alert> : null}

                {step === 1 ? (
                    <>
                        <Field label="Full name">
                            <Input
                                required
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                        </Field>
                        <Field label="Email">
                            <Input
                                type="email"
                                required
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                            />
                        </Field>
                        <Field label="Phone">
                            <Input
                                placeholder="07X XXX XXXX"
                                value={form.data.phone}
                                onChange={(event) => form.setData('phone', event.target.value)}
                            />
                        </Field>
                        <button
                            type="submit"
                            className="w-full cursor-pointer rounded-full bg-brand-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                        >
                            Continue
                        </button>
                    </>
                ) : (
                    <>
                        <Field label="Password">
                            <Input
                                type="password"
                                required
                                autoComplete="new-password"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                            />
                        </Field>
                        <PasswordRules password={form.data.password} />
                        <Field label="Confirm password">
                            <Input
                                type="password"
                                required
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            />
                        </Field>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                className="cursor-pointer rounded-full border border-line bg-white px-4 py-2.5 text-sm font-bold text-brand-900 transition hover:border-brand-700"
                                onClick={() => setStep(1)}
                            >
                                Back
                            </button>
                            <button
                                type="submit"
                                className="flex-1 cursor-pointer rounded-full bg-brand-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:opacity-55"
                                disabled={form.processing}
                            >
                                {form.processing ? 'Creating…' : 'Create account'}
                            </button>
                        </div>
                    </>
                )}
                <p className="mt-4 text-sm">
                    Already have an account?{' '}
                    <Link className="font-bold text-brand-800" href="/login">
                        Log in
                    </Link>
                </p>
            </form>
        </div>
    );
};

Register.layout = withAuthLayout;

export default Register;
