import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { resolve as resolveDispute } from '@/actions/App/Http/Controllers/Admin/AdminDisputeController';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import { Field, Input, Select, Textarea } from '@/components/booktrips/field';
import Pagination from '@/components/booktrips/pagination';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import { lkr } from '@/lib/booktrips';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type DisputeRow = {
    id: number;
    type: string;
    type_label: string;
    status: string;
    summary: string;
    details: string | null;
    response: string | null;
    responded_at: string | null;
    response_deadline_at: string | null;
    can_resolve: boolean;
    resolution: string | null;
    resolution_label: string | null;
    penalty_label: string | null;
    penalty_amount_lkr: number | null;
    resolution_note: string | null;
    resolved_at: string | null;
    created_at: string | null;
    booking: {
        id: number;
        booking_code: string;
        check_in: string;
        total_lkr: number;
        status: string;
        traveller: { id?: number; name?: string; strikes?: number };
    } | null;
    business: { id: number; name: string; strikes: number } | null;
    raised_by: { id: number; name: string; role: string } | null;
    against: { id: number; name: string; strikes: number } | null;
};

type AdminDisputesProps = {
    disputes: Paginated<DisputeRow>;
    filters: { status: string; type: string; q: string };
    types: Array<{ value: string; label: string }>;
    resolutions: Array<{ value: string; label: string }>;
    counts: { open: number; awaiting: number; ready: number };
};

const STATUS_TONES: Record<string, string> = {
    awaiting_response: 'bg-orange-50 text-warn',
    under_review: 'bg-brand-50 text-brand-950',
    resolved: 'bg-cream-dark text-muted',
};

const AdminDisputes: InertiaComponent<AdminDisputesProps> = ({
    disputes,
    filters,
    types,
    resolutions,
    counts,
}) => {
    const [q, setQ] = useState(filters.q);
    const [selected, setSelected] = useState<DisputeRow | null>(null);

    const form = useForm({
        resolution: resolutions[0]?.value ?? 'no_fault',
        penalty: 'none',
        penalty_amount_lkr: 0,
        resolution_note: '',
    });

    function filter(patch: Record<string, string>) {
        router.get(
            '/admin/disputes',
            { ...filters, ...patch },
            { preserveScroll: true, preserveState: true },
        );
    }

    function submitVerdict(event: React.FormEvent) {
        event.preventDefault();

        if (!selected) {
            return;
        }

        form.patch(resolveDispute.url(selected.id), {
            preserveScroll: true,
            onSuccess: () => {
                setSelected(null);
                form.reset();
            },
        });
    }

    const penaltyApplies =
        form.data.resolution === 'customer_fault' ||
        form.data.resolution === 'partner_fault';

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-2xl">
                    Reports between travellers and partners
                </h2>
                <span className="text-muted text-[13px]">
                    {counts.open} open · {counts.awaiting} awaiting a reply ·{' '}
                    {counts.ready} ready to decide
                </span>
            </div>

            <form
                className="mb-4 flex flex-wrap gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    filter({ q });
                }}
            >
                <Input
                    className="min-w-[200px] flex-1"
                    placeholder="Search booking code, business or headline"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                />
                <select
                    className="input-base w-auto cursor-pointer"
                    value={filters.status}
                    onChange={(event) => filter({ status: event.target.value })}
                >
                    <option value="open">Open reports</option>
                    <option value="resolved">Resolved</option>
                    <option value="all">Everything</option>
                </select>
                <select
                    className="input-base w-auto cursor-pointer"
                    value={filters.type}
                    onChange={(event) => filter({ type: event.target.value })}
                >
                    <option value="">All types</option>
                    {types.map((type) => (
                        <option key={type.value} value={type.value}>
                            {type.label}
                        </option>
                    ))}
                </select>
                <Button type="submit">Search</Button>
            </form>

            <div className="border-line overflow-x-auto rounded-2xl border bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-muted text-[11px] tracking-wide uppercase">
                            <th className="px-3.5 py-3 font-bold">Booking</th>
                            <th className="px-3.5 py-3 font-bold">Business</th>
                            <th className="px-3.5 py-3 font-bold">Report</th>
                            <th className="px-3.5 py-3 font-bold">Strikes</th>
                            <th className="px-3.5 py-3 font-bold">Status</th>
                            <th className="px-3.5 py-3 text-right font-bold" />
                        </tr>
                    </thead>
                    <tbody>
                        {disputes.data.map((dispute) => (
                            <tr
                                key={dispute.id}
                                className="border-line border-t align-top"
                            >
                                <td className="px-3.5 py-3 text-sm">
                                    <strong>
                                        {dispute.booking?.booking_code}
                                    </strong>
                                    <div className="text-muted text-xs">
                                        {dispute.booking?.check_in} ·{' '}
                                        {lkr(dispute.booking?.total_lkr ?? 0)}
                                    </div>
                                    <div className="text-muted text-xs">
                                        {dispute.booking?.traveller?.name}
                                    </div>
                                </td>
                                <td className="px-3.5 py-3 text-sm">
                                    {dispute.business?.name}
                                </td>
                                <td className="max-w-[280px] px-3.5 py-3 text-sm">
                                    <strong className="block">
                                        {dispute.type_label}
                                    </strong>
                                    <span className="text-muted">
                                        {dispute.summary}
                                    </span>
                                </td>
                                <td className="px-3.5 py-3 text-xs">
                                    <div>
                                        Traveller:{' '}
                                        {dispute.against?.strikes ??
                                            dispute.booking?.traveller
                                                ?.strikes ??
                                            0}
                                    </div>
                                    <div>
                                        Business:{' '}
                                        {dispute.business?.strikes ?? 0}
                                    </div>
                                </td>
                                <td className="px-3.5 py-3">
                                    <span
                                        className={cn(
                                            'rounded-full px-2.5 py-1 text-[11px] font-bold whitespace-nowrap',
                                            STATUS_TONES[dispute.status] ??
                                                'bg-cream-dark text-muted',
                                        )}
                                    >
                                        {dispute.status === 'awaiting_response'
                                            ? 'Awaiting reply'
                                            : dispute.status === 'under_review'
                                              ? 'Needs a verdict'
                                              : dispute.resolution_label}
                                    </span>
                                </td>
                                <td className="px-3.5 py-3 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        className="text-brand-800 cursor-pointer border-0 bg-transparent text-[13px] font-bold"
                                        onClick={() => {
                                            setSelected(dispute);
                                            form.setData({
                                                resolution: 'no_fault',
                                                penalty: 'none',
                                                penalty_amount_lkr: 0,
                                                resolution_note: '',
                                            });
                                        }}
                                    >
                                        {dispute.status === 'resolved'
                                            ? 'View'
                                            : 'Review'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {disputes.data.length === 0 ? (
                    <p className="text-muted px-4 py-4 text-sm">
                        No reports match these filters.
                    </p>
                ) : null}
            </div>
            <Pagination
                page={disputes.current_page}
                lastPage={disputes.last_page}
                total={disputes.total}
            />

            {selected ? (
                <div
                    className="fixed inset-0 z-120 flex items-start justify-center overflow-y-auto bg-[#0b1d36]/70 p-4 py-10"
                    role="dialog"
                    aria-modal="true"
                    onClick={() => setSelected(null)}
                >
                    <div
                        className="border-line shadow-card w-full max-w-[760px] rounded-[20px] border bg-white p-6"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="text-3xl">
                                    {selected.type_label}
                                </h2>
                                <p className="text-muted">
                                    {selected.booking?.booking_code} ·{' '}
                                    {selected.business?.name} ·{' '}
                                    {selected.booking?.traveller?.name}
                                </p>
                            </div>
                            <span
                                className={cn(
                                    'rounded-full px-2.5 py-1 text-[11px] font-bold',
                                    STATUS_TONES[selected.status] ??
                                        'bg-cream-dark text-muted',
                                )}
                            >
                                {selected.status.replace('_', ' ')}
                            </span>
                        </div>

                        <div className="bg-cream mb-3 rounded-xl px-3.5 py-3">
                            <span className="text-muted text-[11px] font-bold tracking-wide uppercase">
                                Report by {selected.raised_by?.name}
                            </span>
                            <p className="text-sm font-semibold">
                                {selected.summary}
                            </p>
                            {selected.details ? (
                                <p className="text-muted mt-1 text-[13px] whitespace-pre-line">
                                    {selected.details}
                                </p>
                            ) : null}
                        </div>

                        <div className="bg-brand-50 mb-3 rounded-xl px-3.5 py-3">
                            <span className="text-muted text-[11px] font-bold tracking-wide uppercase">
                                Response{' '}
                                {selected.responded_at
                                    ? `· ${new Date(selected.responded_at).toLocaleString('en-GB')}`
                                    : '· still waiting'}
                            </span>
                            <p className="text-[13px] whitespace-pre-line">
                                {selected.response ||
                                    'The other side has not replied yet.'}
                            </p>
                            {selected.response_deadline_at ? (
                                <p className="text-muted mt-1 text-[12px]">
                                    Deadline:{' '}
                                    {new Date(
                                        selected.response_deadline_at,
                                    ).toLocaleString('en-GB')}
                                </p>
                            ) : null}
                        </div>

                        {selected.status === 'resolved' ? (
                            <div className="border-line mb-3 rounded-xl border px-3.5 py-3 text-sm">
                                <strong>
                                    {selected.resolution_label}
                                    {selected.penalty_label
                                        ? ` · ${selected.penalty_label}`
                                        : ''}
                                    {selected.penalty_amount_lkr
                                        ? ` · ${lkr(selected.penalty_amount_lkr)}`
                                        : ''}
                                </strong>
                                {selected.resolution_note ? (
                                    <p className="text-muted mt-1 text-[13px]">
                                        {selected.resolution_note}
                                    </p>
                                ) : null}
                            </div>
                        ) : selected.can_resolve ? (
                            <form
                                onSubmit={submitVerdict}
                                className="border-line rounded-xl border p-4"
                            >
                                <h3 className="mb-3 font-sans text-base font-bold">
                                    Verdict
                                </h3>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <Field label="Who was at fault?">
                                        <Select
                                            value={form.data.resolution}
                                            onChange={(event) =>
                                                form.setData(
                                                    'resolution',
                                                    event.target.value,
                                                )
                                            }
                                        >
                                            {resolutions.map((resolution) => (
                                                <option
                                                    key={resolution.value}
                                                    value={resolution.value}
                                                >
                                                    {resolution.label}
                                                </option>
                                            ))}
                                        </Select>
                                    </Field>
                                    <Field label="Penalty">
                                        <Select
                                            value={form.data.penalty}
                                            disabled={!penaltyApplies}
                                            onChange={(event) =>
                                                form.setData(
                                                    'penalty',
                                                    event.target.value,
                                                )
                                            }
                                        >
                                            <option value="none">
                                                No penalty
                                            </option>
                                            <option value="warning">
                                                Formal warning
                                            </option>
                                            <option value="strike">
                                                Strike (3 suspends)
                                            </option>
                                            <option value="suspend">
                                                Suspend now
                                            </option>
                                        </Select>
                                    </Field>
                                </div>
                                {form.data.resolution === 'partner_fault' ? (
                                    <Field label="Bill the partner (LKR, added to their invoice)">
                                        <Input
                                            type="number"
                                            min={0}
                                            value={form.data.penalty_amount_lkr}
                                            onChange={(event) =>
                                                form.setData(
                                                    'penalty_amount_lkr',
                                                    Number(event.target.value),
                                                )
                                            }
                                        />
                                    </Field>
                                ) : null}
                                <Field label="Note to both parties">
                                    <Textarea
                                        placeholder="Explain the decision — this is sent to both sides."
                                        value={form.data.resolution_note}
                                        onChange={(event) =>
                                            form.setData(
                                                'resolution_note',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                {Object.values(form.errors)[0] ? (
                                    <Alert tone="error">
                                        {Object.values(form.errors)[0]}
                                    </Alert>
                                ) : null}
                                <div className="flex gap-2">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        {form.processing
                                            ? 'Saving…'
                                            : 'Resolve report'}
                                    </Button>
                                    <button
                                        type="button"
                                        className="border-line text-brand-900 cursor-pointer rounded-full border bg-white px-4 py-2.5 text-sm font-bold"
                                        onClick={() => setSelected(null)}
                                    >
                                        Close
                                    </button>
                                </div>
                            </form>
                        ) : (
                            <Alert tone="warn">
                                The other side still has time to respond — you
                                can decide once they reply or the deadline
                                passes.
                            </Alert>
                        )}

                        <div className="mt-4 flex flex-wrap gap-3 text-[13px]">
                            <Link
                                className="text-brand-800 font-bold"
                                href={`/admin/bookings?q=${selected.booking?.booking_code ?? ''}`}
                            >
                                Booking history
                            </Link>
                            {selected.business ? (
                                <Link
                                    className="text-brand-800 font-bold"
                                    href={`/admin/businesses/${selected.business.id}`}
                                >
                                    Business history
                                </Link>
                            ) : null}
                            {selected.booking?.traveller?.id ? (
                                <Link
                                    className="text-brand-800 font-bold"
                                    href={`/admin/travellers/${selected.booking.traveller.id}`}
                                >
                                    Traveller history
                                </Link>
                            ) : null}
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
};

AdminDisputes.layout = withAppLayout;

export default AdminDisputes;
