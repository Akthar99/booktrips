import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { store as receiptStore } from '@/actions/App/Http/Controllers/Partner/PartnerPaymentController';
import Alert from '@/components/booktrips/alert';
import { Field, Input } from '@/components/booktrips/field';
import StatusBadge from '@/components/booktrips/status-badge';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { formatDateTime, lkr } from '@/lib/booktrips';
import type { BankDetails, InvoiceData } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type PaymentsProps = {
    bank: BankDetails;
    commissionRate: number;
    invoices: InvoiceData[];
};

function ReceiptForm({ invoice }: { invoice: InvoiceData }) {
    const form = useForm<{
        invoice_id: number;
        note: string;
        file: File | null;
    }>({
        invoice_id: invoice.id,
        note: '',
        file: null,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(receiptStore.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <form onSubmit={submit} className="mt-3">
            {Object.values(form.errors)[0] ? (
                <Alert tone="error">{Object.values(form.errors)[0]}</Alert>
            ) : null}
            <Field label="Receipt photo or PDF">
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    className="text-sm"
                    onChange={(event) =>
                        form.setData('file', event.target.files?.[0] ?? null)
                    }
                />
            </Field>
            <Field label="Note">
                <Input
                    placeholder="Transfer reference"
                    value={form.data.note}
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                />
            </Field>
            <button
                type="submit"
                className="bg-brand-800 hover:bg-brand-900 cursor-pointer rounded-full px-3.5 py-2 text-[13px] font-bold text-white transition disabled:opacity-55"
                disabled={form.processing || !form.data.file}
            >
                {form.processing ? 'Uploading…' : 'Submit receipt'}
            </button>
        </form>
    );
}

const Payments: InertiaComponent<PaymentsProps> = ({
    bank,
    commissionRate,
    invoices,
}) => {
    const percent = Math.round(commissionRate * 100);
    const [expanded, setExpanded] = useState<number | null>(
        invoices.find((invoice) => invoice.status !== 'paid')?.id ?? null,
    );

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Finance</h1>
            <Tabs
                items={[
                    {
                        label: 'Overview',
                        href: '/partners/dashboard',
                        exact: true,
                    },
                    { label: 'Reservations', href: '/partners/bookings' },
                    { label: 'Analytics', href: '/partners/analytics' },
                    { label: 'Finance', href: '/partners/payments' },
                ]}
            />
            <p className="text-muted mb-4">
                You do not pay per booking. Finished stays add {percent}% to one
                bill dated the last day of the month. Transfer that total once
                and upload the receipt.
            </p>

            <div className="border-line mb-5 rounded-[14px] border bg-white px-4 py-3.5">
                <strong>Transfer to</strong>
                <p>{bank.bank_name}</p>
                <p>
                    {bank.account_name} · {bank.account_number}
                </p>
                <p className="text-muted">
                    {bank.branch} · SWIFT {bank.swift}
                </p>
                <p className="mt-2 text-[13px]">{bank.note}</p>
            </div>

            {invoices.length === 0 ? (
                <p className="text-muted">
                    No bills yet. They appear after you mark a booking finished.
                </p>
            ) : null}

            {invoices.map((invoice) => (
                <div
                    key={invoice.id}
                    className="border-line mb-3 rounded-[14px] border bg-white px-4 py-3.5"
                >
                    <div className="flex flex-wrap justify-between gap-3">
                        <div>
                            <strong>{invoice.period_label}</strong>
                            <div className="text-muted text-[13px]">
                                Due {invoice.due_date} ·{' '}
                                {(invoice.lines ?? []).length} finished bookings
                                {invoice.paid_at
                                    ? ` · paid ${formatDateTime(invoice.paid_at)}`
                                    : ''}
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="text-brand-900 font-extrabold">
                                {lkr(invoice.amount_lkr)}
                            </div>
                            <StatusBadge status={invoice.status} />
                        </div>
                    </div>

                    {invoice.status !== 'paid' ? (
                        <>
                            <button
                                type="button"
                                className="text-brand-800 mt-2.5 cursor-pointer border-0 bg-transparent p-0 text-[13px] font-bold"
                                onClick={() =>
                                    setExpanded(
                                        expanded === invoice.id
                                            ? null
                                            : invoice.id,
                                    )
                                }
                            >
                                {expanded === invoice.id
                                    ? 'Hide receipt upload'
                                    : 'Upload a receipt'}
                            </button>
                            {expanded === invoice.id ? (
                                <ReceiptForm invoice={invoice} />
                            ) : null}
                        </>
                    ) : null}

                    {invoice.receipts.map((receipt) => (
                        <p key={receipt.id} className="mt-2 text-[13px]">
                            Receipt {receipt.status} ·{' '}
                            {formatDateTime(receipt.created_at)} ·{' '}
                            <a
                                className="text-brand-800 font-bold"
                                href={receipt.file_url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                view
                            </a>
                        </p>
                    ))}
                </div>
            ))}
        </div>
    );
};

Payments.layout = withAppLayout;

export default Payments;
