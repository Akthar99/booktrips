import { useEffect, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import {
    confirm as partnerPhoneConfirm,
    send as partnerPhoneSend,
} from '@/actions/App/Http/Controllers/Partner/PartnerPhoneVerificationController';
import { store as partnerRegister } from '@/actions/App/Http/Controllers/Partner/PartnerRegistrationController';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import { Field, Input, Select, Textarea } from '@/components/booktrips/field';
import PasswordRules from '@/components/booktrips/password-rules';
import { withAuthLayout } from '@/layouts/app-layout';
import { useCountdown } from '@/lib/use-countdown';
import type { InertiaComponent } from '@/types/inertia';

type ApplyProps = {
    businessTypes: Array<{ slug: string; name: string }>;
    mode: 'register' | 'upgrade';
    account: { name: string; email: string; phone: string } | null;
    verifiedPhone: string | null;
};

/** Mirror the server-side normalisation so the UI can recognise a verified number. */
function normalisePhone(phone: string): string {
    let digits = phone.replace(/[^0-9]/g, '');

    if (digits.startsWith('0094')) {
        digits = digits.slice(4);
    } else if (digits.startsWith('94') && digits.length === 11) {
        digits = digits.slice(2);
    } else if (digits.startsWith('0') && digits.length === 10) {
        digits = digits.slice(1);
    }

    return /^7\d{8}$/.test(digits) ? `94${digits}` : '';
}

const Apply: InertiaComponent<ApplyProps> = ({ businessTypes, mode, account, verifiedPhone }) => {
    const upgrading = mode === 'upgrade';
    const form = useForm({
        name: account?.name ?? '',
        email: account?.email ?? '',
        phone: account?.phone ?? '',
        password: '',
        password_confirmation: '',
        business_name: '',
        type: businessTypes[0]?.slug ?? 'hotel',
        description: '',
        address: '',
        city: '',
        district: '',
        website: '',
        instagram: '',
        facebook: '',
        tiktok: '',
        whatsapp: '',
        cover_image: '',
    });

    const [otpSent, setOtpSent] = useState(false);
    const [otpCode, setOtpCode] = useState('');
    const [verified, setVerified] = useState(false);
    const [otpBusy, setOtpBusy] = useState(false);
    const [otpError, setOtpError] = useState('');
    const [otpNote, setOtpNote] = useState('');
    const resend = useCountdown();
    // "socials" is a cross-field rule, so it is not part of the form's own keys.
    const socialsError = (form.errors as Record<string, string | undefined>).socials;

    // A number verified earlier in this session stays valid while the tab is open.
    useEffect(() => {
        if (!verifiedPhone || !form.data.phone) {
            return;
        }

        if (verifiedPhone === normalisePhone(form.data.phone)) {
            setVerified(true);
        }
    }, [verifiedPhone, form.data.phone]);

    function xsrfToken(): string {
        return decodeURIComponent(
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        );
    }

    async function sendCode() {
        setOtpBusy(true);
        setOtpError('');
        setOtpNote('');

        try {
            const response = await fetch(partnerPhoneSend.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ phone: form.data.phone }),
            });

            const data = (await response.json().catch(() => ({}))) as {
                ok?: boolean;
                message?: string;
                cooldown?: number;
                errors?: Record<string, string[]>;
            };

            if (!response.ok || !data.ok) {
                setOtpError(
                    data.errors ? (Object.values(data.errors).flat()[0] as string) : (data.message ?? 'Could not send the code.'),
                );
                resend.start(Math.max(0, data.cooldown ?? 0));

                return;
            }

            setOtpSent(true);
            resend.start(data.cooldown ?? 60);
            setOtpNote('Code sent. Check your SMS.');
        } catch {
            setOtpError('Could not send the code. Check your connection and try again.');
        } finally {
            setOtpBusy(false);
        }
    }

    async function verifyCode() {
        setOtpBusy(true);
        setOtpError('');
        setOtpNote('');

        try {
            const response = await fetch(partnerPhoneConfirm.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ phone: form.data.phone, code: otpCode }),
            });

            const data = (await response.json().catch(() => ({}))) as {
                ok?: boolean;
                message?: string;
                errors?: Record<string, string[]>;
            };

            if (!response.ok || !data.ok) {
                setOtpError(
                    data.errors ? (Object.values(data.errors).flat()[0] as string) : (data.message ?? 'Could not verify the code.'),
                );

                return;
            }

            setVerified(true);
            setOtpNote('Mobile number verified.');
        } catch {
            setOtpError('Could not verify the code. Check your connection and try again.');
        } finally {
            setOtpBusy(false);
        }
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        if (!verified) {
            setOtpError('Verify your mobile number before submitting the application.');

            return;
        }

        form.post(partnerRegister.url());
    }

    return (
        <div className="mx-auto w-[min(720px,calc(100%-2rem))] py-7 pb-14">
            <p className="text-[13px] text-muted">
                <Link href="/partners">For partners</Link> · Apply
            </p>
            <h1 className="mt-2 text-4xl">{upgrading ? 'Become a partner' : 'Request to join'}</h1>
            <p className="mt-2 mb-5 text-muted">
                {upgrading
                    ? 'Your traveller account becomes a partner account — same login, same bookings. Our team reviews every request before the panel opens.'
                    : 'Tell us who you are and where guests can find you. Our team reviews every request before the panel opens.'}
            </p>
            {upgrading ? (
                <Alert tone="note">
                    You are signed in as {account?.email}. Submitting upgrades this account, so there is no new
                    password to choose and your past bookings stay with you.
                </Alert>
            ) : null}
            {form.hasErrors && Object.keys(form.errors).length > 0 ? (
                <Alert tone="error">Please review the highlighted fields and try again.</Alert>
            ) : null}
            <form
                onSubmit={submit}
                className="mt-4 rounded-card border border-line bg-white p-6"
                noValidate
            >
                <h3 className="mb-3 font-sans text-lg font-bold">Owner account</h3>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Your name">
                        <Input
                            required
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                        {form.errors.name ? <Alert tone="error">{form.errors.name}</Alert> : null}
                    </Field>
                    <Field label="Phone">
                        <Input
                            required
                            value={form.data.phone}
                            onChange={(event) => {
                                form.setData('phone', event.target.value);
                                setVerified(false);
                                setOtpSent(false);
                                setOtpCode('');
                            }}
                        />
                        {form.errors.phone ? <Alert tone="error">{form.errors.phone}</Alert> : null}
                    </Field>
                </div>

                <div className="mb-3 rounded-xl border border-line bg-cream px-3.5 py-3">
                    <div className="mb-2 flex items-center gap-2 text-[13px] font-bold text-brand-900">
                        {verified ? <CheckCircle2 size={15} /> : null}
                        {verified ? 'Mobile number verified' : 'Verify your mobile number'}
                    </div>
                    {!verified ? (
                        <>
                            <p className="mb-2 text-[12px] text-muted">
                                We text a 6-digit code to confirm the number belongs to you. Applications with a
                                verified number are reviewed first.
                            </p>
                            <div className="flex flex-wrap items-center gap-2">
                                <Button
                                    type="button"
                                    disabled={otpBusy || resend.active}
                                    onClick={sendCode}
                                >
                                    {resend.active
                                        ? `Resend in ${resend.seconds}s`
                                        : otpSent
                                          ? 'Resend code'
                                          : 'Send code'}
                                </Button>
                                {otpSent ? (
                                    <>
                                        <Input
                                            className="w-32"
                                            placeholder="123456"
                                            inputMode="numeric"
                                            maxLength={6}
                                            value={otpCode}
                                            onChange={(event) =>
                                                setOtpCode(event.target.value.replace(/[^0-9]/g, ''))
                                            }
                                        />
                                        <Button
                                            type="button"
                                            disabled={otpBusy || otpCode.length < 4}
                                            onClick={verifyCode}
                                        >
                                            Verify
                                        </Button>
                                    </>
                                ) : null}
                            </div>
                        </>
                    ) : null}
                    {otpError ? <Alert tone="error">{otpError}</Alert> : null}
                    {otpNote && !otpError ? <Alert tone="note">{otpNote}</Alert> : null}
                </div>
                {upgrading ? (
                    <Field label="Email">
                        <Input value={account?.email ?? ''} readOnly disabled />
                    </Field>
                ) : (
                    <>
                        <Field label="Email">
                            <Input
                                type="email"
                                required
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                            />
                            {form.errors.email ? <Alert tone="error">{form.errors.email}</Alert> : null}
                        </Field>
                        <Field label="Password">
                            <Input
                                type="password"
                                required
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                            />
                            {form.errors.password ? <Alert tone="error">{form.errors.password}</Alert> : null}
                        </Field>
                        <PasswordRules password={form.data.password} />
                        <Field label="Confirm password">
                            <Input
                                type="password"
                                required
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            />
                        </Field>
                    </>
                )}

                <h3 className="mt-4 mb-3 font-sans text-lg font-bold">Business</h3>
                <Field label="Business name">
                    <Input
                        required
                        value={form.data.business_name}
                        onChange={(event) => form.setData('business_name', event.target.value)}
                    />
                    {form.errors.business_name ? <Alert tone="error">{form.errors.business_name}</Alert> : null}
                </Field>
                <Field label="Type">
                    <Select
                        value={form.data.type}
                        onChange={(event) => form.setData('type', event.target.value)}
                    >
                        {businessTypes.map((type) => (
                            <option key={type.slug} value={type.slug}>
                                {type.name}
                            </option>
                        ))}
                    </Select>
                </Field>
                <Field label="About the business">
                    <Textarea
                        required
                        placeholder="What you host, where, who it’s for…"
                        value={form.data.description}
                        onChange={(event) => form.setData('description', event.target.value)}
                    />
                    {form.errors.description ? <Alert tone="error">{form.errors.description}</Alert> : null}
                </Field>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="City">
                        <Input
                            required
                            value={form.data.city}
                            onChange={(event) => form.setData('city', event.target.value)}
                        />
                        {form.errors.city ? <Alert tone="error">{form.errors.city}</Alert> : null}
                    </Field>
                    <Field label="District">
                        <Input
                            value={form.data.district}
                            onChange={(event) => form.setData('district', event.target.value)}
                        />
                    </Field>
                </div>
                <Field label="Address">
                    <Input
                        value={form.data.address}
                        onChange={(event) => form.setData('address', event.target.value)}
                    />
                </Field>

                <h3 className="mt-4 mb-3 font-sans text-lg font-bold">Social &amp; web</h3>
                <p className="-mt-2 mb-3 text-[13px] text-muted">
                    Add at least one of these — we check it before approving your application.
                </p>
                {socialsError ? <Alert tone="error">{socialsError}</Alert> : null}
                <Field label="Website">
                    <Input
                        placeholder="https://"
                        value={form.data.website}
                        onChange={(event) => form.setData('website', event.target.value)}
                    />
                </Field>
                <Field label="Instagram">
                    <Input
                        placeholder="https://instagram.com/…"
                        value={form.data.instagram}
                        onChange={(event) => form.setData('instagram', event.target.value)}
                    />
                </Field>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Facebook">
                        <Input
                            value={form.data.facebook}
                            onChange={(event) => form.setData('facebook', event.target.value)}
                        />
                    </Field>
                    <Field label="TikTok">
                        <Input
                            value={form.data.tiktok}
                            onChange={(event) => form.setData('tiktok', event.target.value)}
                        />
                    </Field>
                </div>
                <Field label="WhatsApp">
                    <Input
                        placeholder="9477…"
                        value={form.data.whatsapp}
                        onChange={(event) => form.setData('whatsapp', event.target.value)}
                    />
                </Field>
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Sending…' : 'Submit request'}
                </Button>
            </form>
        </div>
    );
};

Apply.layout = withAuthLayout;

export default Apply;
