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
        terms: false,
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
                className="border-line shadow-card w-full max-w-[440px] rounded-[20px] border bg-white p-7"
                noValidate
            >
                <p className="text-brand-800 mb-2 text-[13px] font-bold tracking-[0.12em] uppercase">
                    Step {step} of 2
                </p>
                <h1 className="mb-2 text-3xl">
                    {step === 1 ? 'Your details' : 'Set a password'}
                </h1>
                <p className="text-muted mb-5">
                    {step === 1
                        ? 'We’ll send a verification link to this email.'
                        : 'Use a strong password. You’ll need it to book.'}
                </p>
                {stepError ? <Alert tone="error">{stepError}</Alert> : null}
                {form.errors.name ? (
                    <Alert tone="error">{form.errors.name}</Alert>
                ) : null}
                {form.errors.email ? (
                    <Alert tone="error">{form.errors.email}</Alert>
                ) : null}
                {form.errors.password ? (
                    <Alert tone="error">{form.errors.password}</Alert>
                ) : null}
                {form.errors.terms ? (
                    <Alert tone="error">{form.errors.terms}</Alert>
                ) : null}

                {step === 1 ? (
                    <>
                        <Field label="Full name">
                            <Input
                                required
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Email">
                            <Input
                                type="email"
                                required
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Phone">
                            <Input
                                placeholder="07X XXX XXXX"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                            />
                        </Field>
                        <button
                            type="submit"
                            className="bg-brand-800 hover:bg-brand-900 w-full cursor-pointer rounded-full px-4 py-2.5 text-sm font-bold text-white transition"
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
                                onChange={(event) =>
                                    form.setData('password', event.target.value)
                                }
                            />
                        </Field>
                        <PasswordRules password={form.data.password} />
                        <Field label="Confirm password">
                            <Input
                                type="password"
                                required
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(event) =>
                                    form.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <label className="text-muted mt-4 flex items-start gap-2.5 text-sm">
                            <input
                                type="checkbox"
                                className="accent-brand-800 mt-0.5 h-4 w-4 shrink-0 cursor-pointer"
                                checked={form.data.terms}
                                onChange={(event) =>
                                    form.setData('terms', event.target.checked)
                                }
                                required
                            />
                            <span>
                                I agree to the{' '}
                                <Link
                                    href="/terms"
                                    target="_blank"
                                    className="text-brand-800 font-bold hover:underline"
                                >
                                    Terms of Service
                                </Link>{' '}
                                and the{' '}
                                <Link
                                    href="/privacy"
                                    target="_blank"
                                    className="text-brand-800 font-bold hover:underline"
                                >
                                    Privacy Policy
                                </Link>
                                .
                            </span>
                        </label>
                        <div className="mt-5 flex gap-2">
                            <button
                                type="button"
                                className="border-line text-brand-900 hover:border-brand-700 cursor-pointer rounded-full border bg-white px-4 py-2.5 text-sm font-bold transition"
                                onClick={() => setStep(1)}
                            >
                                Back
                            </button>
                            <button
                                type="submit"
                                className="bg-brand-800 hover:bg-brand-900 flex-1 cursor-pointer rounded-full px-4 py-2.5 text-sm font-bold text-white transition disabled:opacity-55"
                                disabled={form.processing}
                            >
                                {form.processing
                                    ? 'Creating…'
                                    : 'Create account'}
                            </button>
                        </div>
                    </>
                )}
                <p className="mt-4 text-sm">
                    Already have an account?{' '}
                    <Link className="text-brand-800 font-bold" href="/login">
                        Log in
                    </Link>
                </p>
            </form>
        </div>
    );
};

Register.layout = withAuthLayout;

export default Register;
