import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { AlertTriangle, Clock, ShieldCheck } from 'lucide-react';
import {
    respond as respondToDispute,
    store as storeDispute,
} from '@/actions/App/Http/Controllers/DisputeController';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import { Field, Select, Textarea } from '@/components/booktrips/field';
import { cn } from '@/lib/utils';

export type DisputeData = {
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
    created_at: string | null;
    raised_by_me: boolean;
    against_me: boolean;
    can_respond: boolean;
};

type ReportType = { value: string; label: string; blurb: string };

const STATUS_TONES: Record<string, string> = {
    awaiting_response: 'bg-orange-50 text-warn',
    under_review: 'bg-brand-50 text-brand-950',
    resolved: 'bg-cream-dark text-muted',
};

/**
 * Reports between the two sides of a booking: raise one, answer one, see verdicts.
 */
export default function DisputePanel({
    bookingId,
    disputes,
    reportTypes,
    canReport,
}: {
    bookingId: number;
    disputes: DisputeData[];
    reportTypes: ReportType[];
    canReport: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [respondingTo, setRespondingTo] = useState<number | null>(null);
    const form = useForm({
        type: reportTypes[0]?.value ?? 'other',
        summary: '',
        details: '',
    });
    const response = useForm({ response: '' });

    function submitReport(event: React.FormEvent) {
        event.preventDefault();
        form.post(storeDispute.url(bookingId), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
        });
    }

    function submitResponse(event: React.FormEvent, disputeId: number) {
        event.preventDefault();
        response.post(respondToDispute.url(disputeId), {
            preserveScroll: true,
            onSuccess: () => {
                setRespondingTo(null);
                response.reset();
            },
        });
    }

    const firstError = Object.values(form.errors)[0] as string | undefined;
    const responseError = Object.values(response.errors)[0] as
        | string
        | undefined;

    return (
        <section
            className="rounded-card border-line mt-4 border bg-white p-5"
            id="reports"
        >
            <div className="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h3 className="font-sans text-base font-bold">
                        Reports &amp; disputes
                    </h3>
                    <p className="text-muted text-[13px]">
                        Something went wrong with this trip? Report it here —
                        the other side always gets to respond before BookTrips
                        decides.
                    </p>
                </div>
                {canReport && !open ? (
                    <button
                        type="button"
                        className="text-danger shrink-0 cursor-pointer rounded-full border border-red-200 bg-white px-3.5 py-2 text-[13px] font-bold transition hover:border-red-400"
                        onClick={() => setOpen(true)}
                    >
                        Report a problem
                    </button>
                ) : null}
            </div>

            {disputes.map((dispute) => (
                <div
                    key={dispute.id}
                    className="border-line mb-3 rounded-xl border p-3.5"
                >
                    <div className="mb-1.5 flex flex-wrap items-center gap-2">
                        <span
                            className={cn(
                                'rounded-full px-2.5 py-1 text-[11px] font-bold',
                                STATUS_TONES[dispute.status] ??
                                    'bg-cream-dark text-muted',
                            )}
                        >
                            {dispute.type_label}
                        </span>
                        <strong className="text-sm">{dispute.summary}</strong>
                        {dispute.raised_by_me ? (
                            <span className="text-muted text-[11px] font-bold">
                                raised by you
                            </span>
                        ) : null}
                    </div>
                    {dispute.details ? (
                        <p className="text-muted text-[13px] whitespace-pre-line">
                            {dispute.details}
                        </p>
                    ) : null}

                    {dispute.status === 'awaiting_response' &&
                    dispute.can_respond ? (
                        <p className="text-warn mt-2 flex items-center gap-1.5 rounded-lg bg-orange-50 px-2.5 py-2 text-[12px] font-semibold">
                            <Clock size={13} /> Tell us your side before{' '}
                            {dispute.response_deadline_at
                                ? new Date(
                                      dispute.response_deadline_at,
                                  ).toLocaleString('en-GB')
                                : 'the deadline'}
                            .
                        </p>
                    ) : null}

                    {dispute.response ? (
                        <div className="bg-cream mt-2 rounded-lg px-3 py-2">
                            <span className="text-muted text-[11px] font-bold tracking-wide uppercase">
                                Response
                            </span>
                            <p className="text-[13px] whitespace-pre-line">
                                {dispute.response}
                            </p>
                        </div>
                    ) : null}

                    {dispute.status === 'resolved' ? (
                        <div className="bg-cream mt-2 rounded-lg px-3 py-2 text-[13px]">
                            <strong>
                                {dispute.resolution_label}
                                {dispute.penalty_label &&
                                dispute.penalty_label !== 'No penalty'
                                    ? ` · ${dispute.penalty_label}`
                                    : ''}
                                {dispute.penalty_amount_lkr
                                    ? ` · Rs. ${dispute.penalty_amount_lkr.toLocaleString()}`
                                    : ''}
                            </strong>
                            {dispute.resolution_note ? (
                                <p className="mt-1">
                                    {dispute.resolution_note}
                                </p>
                            ) : null}
                        </div>
                    ) : null}

                    {dispute.can_respond &&
                    dispute.status === 'awaiting_response' ? (
                        respondingTo === dispute.id ? (
                            <form
                                className="mt-2"
                                onSubmit={(event) =>
                                    submitResponse(event, dispute.id)
                                }
                            >
                                <Textarea
                                    placeholder="What happened from your side? Include anything you can show us."
                                    value={response.data.response}
                                    onChange={(event) =>
                                        response.setData(
                                            'response',
                                            event.target.value,
                                        )
                                    }
                                />
                                {responseError ? (
                                    <Alert tone="error">{responseError}</Alert>
                                ) : null}
                                <div className="mt-2 flex gap-2">
                                    <Button
                                        type="submit"
                                        disabled={response.processing}
                                    >
                                        {response.processing
                                            ? 'Sending…'
                                            : 'Send my side'}
                                    </Button>
                                    <button
                                        type="button"
                                        className="border-line text-brand-900 cursor-pointer rounded-full border bg-white px-4 py-2.5 text-sm font-bold"
                                        onClick={() => setRespondingTo(null)}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        ) : (
                            <button
                                type="button"
                                className="bg-brand-800 hover:bg-brand-900 mt-2 cursor-pointer rounded-full px-3.5 py-2 text-[13px] font-bold text-white transition"
                                onClick={() => setRespondingTo(dispute.id)}
                            >
                                Respond to this report
                            </button>
                        )
                    ) : null}
                </div>
            ))}

            {disputes.length === 0 ? (
                <p className="text-muted flex items-center gap-2 text-[13px]">
                    <ShieldCheck size={15} /> No reports on this booking.
                </p>
            ) : null}

            {open ? (
                <form
                    onSubmit={submitReport}
                    className="border-line bg-cream mt-2 rounded-xl border p-3.5"
                >
                    <h4 className="text-brand-900 mb-2 flex items-center gap-2 text-sm font-bold">
                        <AlertTriangle size={15} /> What went wrong?
                    </h4>
                    <Field label="Type of problem">
                        <Select
                            value={form.data.type}
                            onChange={(event) =>
                                form.setData('type', event.target.value)
                            }
                        >
                            {reportTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <p className="text-muted -mt-2 mb-3 text-[12px]">
                        {
                            reportTypes.find(
                                (type) => type.value === form.data.type,
                            )?.blurb
                        }
                    </p>
                    <Field label="Short headline">
                        <Textarea
                            className="min-h-[60px]"
                            placeholder="Guest did not arrive and never cancelled"
                            value={form.data.summary}
                            onChange={(event) =>
                                form.setData('summary', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="What happened? (optional)">
                        <Textarea
                            placeholder="Dates, times, who you spoke to, anything that proves your side."
                            value={form.data.details}
                            onChange={(event) =>
                                form.setData('details', event.target.value)
                            }
                        />
                    </Field>
                    {firstError ? (
                        <Alert tone="error">{firstError}</Alert>
                    ) : null}
                    <div className="mt-1 flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Sending…' : 'Send report'}
                        </Button>
                        <button
                            type="button"
                            className="border-line text-brand-900 cursor-pointer rounded-full border bg-white px-4 py-2.5 text-sm font-bold"
                            onClick={() => {
                                setOpen(false);
                                router.reload({ only: ['disputes'] });
                            }}
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            ) : null}
        </section>
    );
}
