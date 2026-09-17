import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import {
    confirm as partnerPhoneConfirm,
    send as partnerPhoneSend,
} from '@/actions/App/Http/Controllers/Partner/PartnerPhoneVerificationController';
import { store as partnerRegister } from '@/actions/App/Http/Controllers/Partner/PartnerRegistrationController';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import { Field, Input, Select, Textarea } from '@/components/booktrips/field';
import PasswordRules from '@/components/booktrips/password-rules';
import PhoneVerification from '@/components/booktrips/phone-verification';
import { withAuthLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

type ApplyProps = {
    businessTypes: Array<{ slug: string; name: string }>;
    mode: 'register' | 'upgrade';
    account: { name: string; email: string; phone: string } | null;
    verifiedPhone: string | null;
};

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

    const [verified, setVerified] = useState(false);
    const [verifyNotice, setVerifyNotice] = useState('');
    // "socials" is a cross-field rule, so it is not part of the form's own keys.
    const socialsError = (form.errors as Record<string, string | undefined>).socials;

    function submit(event: React.FormEvent) {
        event.preventDefault();

        if (!verified) {
            setVerifyNotice('Verify your mobile number before submitting the application.');

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
                            onChange={(event) => form.setData('phone', event.target.value)}
                        />
                        {form.errors.phone ? <Alert tone="error">{form.errors.phone}</Alert> : null}
                    </Field>
                </div>

                <PhoneVerification
                    phone={form.data.phone}
                    onPhoneChange={(value) => form.setData('phone', value)}
                    verifiedPhone={verifiedPhone}
                    sendUrl={partnerPhoneSend.url()}
                    confirmUrl={partnerPhoneConfirm.url()}
                    onVerifiedChange={(isVerified) => {
                        setVerified(isVerified);

                        if (isVerified) {
                            setVerifyNotice('');
                        }
                    }}
                    hint="We text a 6-digit code to confirm the number belongs to you. Applications with a verified number are reviewed first."
                />
                {verifyNotice ? <Alert tone="error">{verifyNotice}</Alert> : null}

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
