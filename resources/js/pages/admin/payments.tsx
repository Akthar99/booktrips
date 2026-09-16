import { useState } from 'react';
import { router } from '@inertiajs/react';
import { updateReceipt } from '@/actions/App/Http/Controllers/Admin/AdminPaymentController';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import StatusBadge from '@/components/booktrips/status-badge';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { formatDateTime, lkr } from '@/lib/booktrips';
import type { BankDetails, InvoiceData } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type AdminReceipt = {
    id: number;
    status: string;
    note: string | null;
    original_name: string | null;
    created_at: string | null;
    reviewed_at: string | null;
    business_name: string;
    period: string;
    amount_lkr: number;
    file_url: string;
};

type AdminPaymentsProps = {
    bank: BankDetails;
    commissionRate: number;
    invoices: InvoiceData[];
    receipts: AdminReceipt[];
};

const AdminPayments: InertiaComponent<AdminPaymentsProps> = ({ invoices, receipts }) => {
    const [pending, setPending] = useState<{ receipt: AdminReceipt; status: string } | null>(null);
    const [busy, setBusy] = useState(false);

    function review(receiptId: number, status: string) {
        setBusy(true);
        router.patch(
            updateReceipt.url(receiptId),
            { status },
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusy(false);
                    setPending(null);
                },
            },
        );
    }

    const outstanding = invoices.filter((invoice) => invoice.status !== 'paid');

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs
                items={[
                    { label: 'Overview', href: '/admin', exact: true },
                    { label: 'Users', href: '/admin/users' },
                    { label: 'Partners', href: '/admin/partners' },
                    { label: 'Listings', href: '/admin/listings' },
                    { label: 'Bookings', href: '/admin/bookings' },
                    { label: 'Finance', href: '/admin/payments' },
                    { label: 'Reviews', href: '/admin/reviews' },
                ]}
            />
            <h2 className="mb-3 text-2xl">Payment receipts</h2>
            <p className="mb-3 text-muted">Confirm a transfer to mark that invoice paid.</p>
            <div className="overflow-x-auto rounded-2xl border border-line bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-[11px] tracking-wide text-muted uppercase">
                            <th className="px-3.5 py-3 font-bold">When</th>
                            <th className="px-3.5 py-3 font-bold">Partner</th>
                            <th className="px-3.5 py-3 font-bold">Period</th>
                            <th className="px-3.5 py-3 font-bold">Amount</th>
                            <th className="px-3.5 py-3 font-bold">Receipt</th>
                            <th className="px-3.5 py-3 font-bold">Status</th>
                            <th className="px-3.5 py-3 text-right font-bold" />
                        </tr>
                    </thead>
                    <tbody>
                        {receipts.map((receipt) => (
                            <tr key={receipt.id} className="border-t border-line">
                                <td className="px-3.5 py-3 text-sm">{formatDateTime(receipt.created_at)}</td>
                                <td className="px-3.5 py-3 text-sm">{receipt.business_name}</td>
                                <td className="px-3.5 py-3 text-sm">{receipt.period}</td>
                                <td className="px-3.5 py-3 text-sm">{lkr(receipt.amount_lkr)}</td>
                                <td className="px-3.5 py-3 text-sm">
                                    <a
                                        className="font-bold text-brand-800"
                                        href={receipt.file_url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        open
                                    </a>
                                </td>
                                <td className="px-3.5 py-3">
                                    <StatusBadge status={receipt.status} />
                                </td>
                                <td className="px-3.5 py-3 text-right whitespace-nowrap">
                                    {receipt.status === 'pending' ? (
                                        <>
                                            <button
                                                type="button"
                                                className="cursor-pointer rounded-full bg-brand-800 px-3 py-1.5 text-[13px] font-bold text-white transition hover:bg-brand-900"
                                                onClick={() => setPending({ receipt, status: 'confirmed' })}
                                            >
                                                Confirm
                                            </button>
                                            <button
                                                type="button"
                                                className="ml-2 cursor-pointer rounded-full border border-red-200 bg-white px-3 py-1.5 text-[13px] font-bold text-danger transition hover:border-red-400"
                                                onClick={() => setPending({ receipt, status: 'rejected' })}
                                            >
                                                Reject
                                            </button>
                                        </>
                                    ) : null}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <h3 className="mt-7 mb-2 font-sans text-lg font-bold">Open invoices</h3>
            {outstanding.length === 0 ? <p className="text-muted">No outstanding bills.</p> : null}
            <div className="grid gap-3">
                {outstanding.map((invoice) => (
                    <div key={invoice.id} className="rounded-[14px] border border-line bg-white px-4 py-3.5">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <strong>{invoice.business_name}</strong>
                                <div className="text-[13px] text-muted">
                                    {invoice.period_label} · due {invoice.due_date} · {invoice.receipts.length}{' '}
                                    receipts
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="font-extrabold text-brand-900">{lkr(invoice.amount_lkr)}</span>
                                <StatusBadge status={invoice.status} />
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <ConfirmDialog
                open={pending !== null}
                title={pending?.status === 'confirmed' ? 'Confirm this receipt?' : 'Reject this receipt?'}
                message={
                    pending?.status === 'confirmed'
                        ? 'This will mark the invoice as paid.'
                        : 'This will reopen the invoice so the partner can submit another receipt.'
                }
                confirmLabel={pending?.status === 'confirmed' ? 'Confirm payment' : 'Reject receipt'}
                danger={pending?.status === 'rejected'}
                busy={busy}
                onConfirm={() => pending && review(pending.receipt.id, pending.status)}
                onCancel={() => setPending(null)}
            />
        </div>
    );
};

AdminPayments.layout = withAppLayout;

export default AdminPayments;
