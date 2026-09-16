import { useEffect, useMemo, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { store as bookingStore } from '@/actions/App/Http/Controllers/BookingController';
import { quote as quoteRoute } from '@/actions/App/Http/Controllers/PackageController';
import Alert from '@/components/booktrips/alert';
import DateInput from '@/components/booktrips/date-input';
import { Field, Input, Textarea } from '@/components/booktrips/field';
import { withAppLayout } from '@/layouts/app-layout';
import { lkr } from '@/lib/booktrips';
import type { PackageDetailData, SharedProps } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type Quote = {
    base_total_lkr: number;
    discount_lkr: number;
    discount_label: string;
    total_lkr: number;
};

type BookProps = { package: PackageDetailData };

const Book: InertiaComponent<BookProps> = ({ package: pkg }) => {
    const page = usePage<SharedProps>();
    const user = page.props.auth.user;

    const initial = useMemo(() => {
        const query = new URLSearchParams(page.url.split('?')[1] ?? '');

        return {
            check_in: query.get('check_in') ?? '',
            check_out: query.get('check_out') ?? '',
            guests: Number(query.get('guests') ?? 2),
        };
    }, [page.url]);

    const [form, setForm] = useState({
        check_in: initial.check_in,
        check_out: initial.check_out,
        guests: initial.guests,
        guest_name: user?.name ?? '',
        guest_phone: user?.phone ?? '',
        notes: '',
    });
    const [quote, setQuote] = useState<Quote | null>(null);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        setForm((current) => ({
            ...current,
            check_in: initial.check_in,
            check_out: initial.check_out,
            guests: initial.guests,
        }));
    }, [initial]);

    useEffect(() => {
        if (!form.check_in || !form.check_out) {
            setQuote(null);

            return undefined;
        }

        const controller = new AbortController();

        fetch(
            quoteRoute.url(pkg.id, {
                query: { check_in: form.check_in, check_out: form.check_out, guests: String(form.guests) },
            }),
            { headers: { Accept: 'application/json' }, signal: controller.signal },
        )
            .then(async (response) => {
                if (response.ok) {
                    setQuote((await response.json()) as Quote);
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [pkg.id, form.check_in, form.check_out, form.guests]);

    function submit(event: React.FormEvent) {
        event.preventDefault();
        setBusy(true);
        setError('');

        fetch(bookingStore.url(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
            },
            body: JSON.stringify({ package_id: pkg.id, ...form }),
        })
            .then(async (response) => {
                if (response.redirected) {
                    window.location.href = response.url;

                    return;
                }

                const data = (await response.json().catch(() => ({}))) as Record<string, unknown>;

                if (!response.ok) {
                    const errors = data.errors as Record<string, string[]> | undefined;
                    setError(errors ? Object.values(errors).flat()[0] : ((data.message as string) || 'Could not send the request.'));

                    return;
                }

                window.location.href = '/account/bookings';
            })
            .catch(() => setError('Something went wrong. Please try again.'))
            .finally(() => setBusy(false));
    }

    if (!user || !user.email_verified) {
        return (
            <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-12">
                <div className="mx-auto max-w-[640px] rounded-[20px] border border-line bg-white p-8 text-center">
                    <h1 className="mb-2 text-3xl">Verify email to book</h1>
                    <p className="mb-5 text-muted">
                        We only confirm trips for verified addresses so hosts can trust the reservation.
                    </p>
                    <Link
                        href="/verify-email"
                        className="inline-flex rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                    >
                        Verify now
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="mx-auto grid w-[min(1180px,calc(100%-2rem))] grid-cols-1 gap-6 py-8 pb-16 lg:grid-cols-[1fr_320px]">
            <form onSubmit={submit} className="rounded-card border border-line bg-white p-6" noValidate>
                <h1 className="text-3xl">Confirm reservation</h1>
                <p className="mt-2 mb-5 text-muted">
                    {pkg.title} · {pkg.location}
                </p>
                {error ? <Alert tone="error">{error}</Alert> : null}
                <div className="grid gap-3 sm:grid-cols-2">
                    <DateInput
                        required
                        label="Start"
                        value={form.check_in}
                        onChange={(check_in) => setForm({ ...form, check_in })}
                    />
                    <DateInput
                        required
                        label="End"
                        value={form.check_out}
                        min={form.check_in || undefined}
                        onChange={(check_out) => setForm({ ...form, check_out })}
                    />
                </div>
                <div className="h-3" />
                <Field label="Guests">
                    <Input
                        type="number"
                        min={pkg.min_guests}
                        max={pkg.max_guests}
                        value={form.guests}
                        onChange={(event) => setForm({ ...form, guests: Number(event.target.value) })}
                    />
                </Field>
                <Field label="Lead guest name">
                    <Input
                        required
                        value={form.guest_name}
                        onChange={(event) => setForm({ ...form, guest_name: event.target.value })}
                    />
                </Field>
                <Field label="Phone">
                    <Input
                        required
                        value={form.guest_phone}
                        onChange={(event) => setForm({ ...form, guest_phone: event.target.value })}
                    />
                </Field>
                <Field label="Notes for the host">
                    <Textarea
                        placeholder="Pickup point, diet, kids…"
                        value={form.notes}
                        onChange={(event) => setForm({ ...form, notes: event.target.value })}
                    />
                </Field>
                <div className="my-2 mb-3.5 rounded-xl bg-brand-50 px-3 py-2.5 text-[13px] text-brand-950">
                    Payment method: <strong>Pay at destination</strong>. No card is charged on BookTrips.
                </div>
                <button
                    type="submit"
                    className="cursor-pointer rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:opacity-55"
                    disabled={busy}
                >
                    {busy ? 'Sending…' : 'Send request'}
                </button>
            </form>

            <aside className="h-fit rounded-card border border-line bg-white p-4.5 shadow-card lg:sticky lg:top-22">
                {pkg.images?.[0] ? (
                    <img src={pkg.images[0]} alt="" className="mb-3 h-35 w-full rounded-xl object-cover" />
                ) : null}
                <h3 className="font-sans text-base font-bold">{pkg.title}</h3>
                <p className="text-[13px] text-muted">{pkg.location}</p>
                {quote ? (
                    <div className="mt-3 text-base font-extrabold text-brand-900">
                        {quote.discount_lkr ? (
                            <del className="block text-xs font-semibold text-muted">{lkr(quote.base_total_lkr)}</del>
                        ) : null}
                        {lkr(quote.total_lkr)}
                        <small className="block text-[11px] font-semibold text-muted">
                            due on arrival{quote.discount_label ? ` · ${quote.discount_label}` : ''}
                        </small>
                    </div>
                ) : null}
            </aside>
        </div>
    );
};

Book.layout = withAppLayout;

export default Book;
