import { useEffect, useState } from 'react';
import { CheckCircle2 } from 'lucide-react';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import { Input } from '@/components/booktrips/field';
import { useCountdown } from '@/lib/use-countdown';

/**
 * Send-code / verify-code block used wherever a mobile number has to be proven
 * (partner applications and bookings).
 */
export default function PhoneVerification({
    phone,
    onPhoneChange,
    verifiedPhone,
    sendUrl,
    confirmUrl,
    onVerifiedChange,
    hint,
}: {
    phone: string;
    onPhoneChange: (value: string) => void;
    /** The number the server already verified in this session, normalised to 947XXXXXXXX. */
    verifiedPhone: string | null;
    sendUrl: string;
    confirmUrl: string;
    onVerifiedChange?: (verified: boolean) => void;
    hint?: string;
}) {
    const [sent, setSent] = useState(false);
    const [code, setCode] = useState('');
    const [verifiedFor, setVerifiedFor] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [note, setNote] = useState('');
    const resend = useCountdown();

    const current = normalisePhone(phone);
    const verified = verifiedFor !== '' && verifiedFor === current;

    useEffect(() => {
        if (verifiedPhone && verifiedPhone === current) {
            setVerifiedFor(current);
        }
    }, [verifiedPhone, current]);

    useEffect(() => {
        onVerifiedChange?.(verified);
    }, [verified]); // eslint-disable-line react-hooks/exhaustive-deps

    function xsrfToken(): string {
        return decodeURIComponent(
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        );
    }

    async function post(url: string, body: Record<string, string>, onOk: (data: Record<string, unknown>) => void) {
        setBusy(true);
        setError('');
        setNote('');

        try {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify(body),
            });

            const data = (await response.json().catch(() => ({}))) as {
                ok?: boolean;
                message?: string;
                cooldown?: number;
                errors?: Record<string, string[]>;
            };

            if (!response.ok || !data.ok) {
                setError(
                    data.errors
                        ? (Object.values(data.errors).flat()[0] as string)
                        : (data.message ?? 'Something went wrong. Please try again.'),
                );
                resend.start(Math.max(0, data.cooldown ?? 0));

                return;
            }

            onOk(data as Record<string, unknown>);
        } catch {
            setError('Could not reach the server. Check your connection and try again.');
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="mb-3 rounded-xl border border-line bg-cream px-3.5 py-3">
            <div className="mb-2 flex items-center gap-2 text-[13px] font-bold text-brand-900">
                {verified ? <CheckCircle2 size={15} /> : null}
                {verified ? 'Mobile number verified' : 'Verify your mobile number'}
            </div>
            {!verified ? (
                <>
                    <p className="mb-2 text-[12px] text-muted">
                        {hint ?? 'We text a 6-digit code to confirm the number belongs to you.'}
                    </p>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button type="button" disabled={busy || resend.active} onClick={() => post(sendUrl, { phone }, () => {
                            setSent(true);
                            setNote('Code sent. Check your SMS.');
                        })}>
                            {resend.active ? `Resend in ${resend.seconds}s` : sent ? 'Resend code' : 'Send code'}
                        </Button>
                        {sent ? (
                            <>
                                <Input
                                    className="w-32"
                                    placeholder="123456"
                                    inputMode="numeric"
                                    maxLength={6}
                                    value={code}
                                    onChange={(event) => setCode(event.target.value.replace(/[^0-9]/g, ''))}
                                />
                                <Button
                                    type="button"
                                    disabled={busy || code.length < 4}
                                    onClick={() =>
                                        post(confirmUrl, { phone, code }, () => {
                                            setVerifiedFor(normalisePhone(phone));
                                            setNote('Mobile number verified.');
                                        })
                                    }
                                >
                                    Verify
                                </Button>
                            </>
                        ) : null}
                    </div>
                </>
            ) : null}
            {error ? <Alert tone="error">{error}</Alert> : null}
            {note && !error ? <Alert tone="note">{note}</Alert> : null}
        </div>
    );
}

/** Mirror of the server-side normalisation so a verified number can be recognised. */
export function normalisePhone(phone: string): string {
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
