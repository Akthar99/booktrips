import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { approve as approvePartner } from '@/actions/App/Http/Controllers/Admin/AdminPartnerController';
import ConfirmDialog from '@/components/booktrips/confirm-dialog';
import { Input } from '@/components/booktrips/field';
import Pagination from '@/components/booktrips/pagination';
import StatusBadge from '@/components/booktrips/status-badge';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import type { Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type PartnerRow = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    description: string | null;
    address: string | null;
    city: string;
    district: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    cover_image: string | null;
    instagram: string | null;
    facebook: string | null;
    tiktok: string | null;
    whatsapp: string | null;
    approved: boolean;
    phone_verified_at: string | null;
    created_at: string | null;
    owner: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        email_verified: boolean;
        joined_at: string | null;
    } | null;
};

type PartnersProps = { businesses: Paginated<PartnerRow> };

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const AdminPartners: InertiaComponent<PartnersProps> = ({ businesses }) => {
    const [q, setQ] = useState('');
    const [pending, setPending] = useState<{ business: PartnerRow; approved: boolean } | null>(null);
    const [detail, setDetail] = useState<PartnerRow | null>(null);
    const [busy, setBusy] = useState(false);

    const filtered = useMemo(() => {
        const needle = q.trim().toLowerCase();

        if (!needle) {
            return businesses.data;
        }

        return businesses.data.filter((business) =>
            [
                business.name,
                business.type,
                business.city,
                business.district,
                business.owner?.name,
                business.owner?.email,
                business.approved ? 'approved' : 'pending',
            ]
                .join(' ')
                .toLowerCase()
                .includes(needle),
        );
    }, [businesses.data, q]);

    function setApproved(businessId: number, approved: boolean) {
        setBusy(true);
        router.patch(
            approvePartner.url(businessId),
            { approved },
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusy(false);
                    setPending(null);
                    setDetail(null);
                },
            },
        );
    }

    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <h2 className="mb-3 text-2xl">Partner requests</h2>
            <form className="my-3" onSubmit={(event) => event.preventDefault()}>
                <Input
                    placeholder="Search business, owner, email, city or status"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                />
            </form>
            <p className="mb-3 text-[13px] text-muted">{filtered.length} shown</p>
            <div className="overflow-x-auto rounded-2xl border border-line bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-[11px] tracking-wide text-muted uppercase">
                            <th className="px-3.5 py-3 font-bold">Business</th>
                            <th className="px-3.5 py-3 font-bold">Owner</th>
                            <th className="px-3.5 py-3 font-bold">City</th>
                            <th className="px-3.5 py-3 font-bold">Social</th>
                            <th className="px-3.5 py-3 font-bold">Status</th>
                            <th className="px-3.5 py-3 text-right font-bold" />
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.map((business) => (
                            <tr key={business.id} className="border-t border-line">
                                <td className="px-3.5 py-3 text-sm">
                                    <strong>{business.name}</strong>
                                    <div className="text-xs text-muted">{business.type.replace('_', ' ')}</div>
                                </td>
                                <td className="px-3.5 py-3 text-sm">
                                    {business.owner?.name}
                                    <br />
                                    <span className="text-muted">{business.owner?.email}</span>
                                </td>
                                <td className="px-3.5 py-3 text-sm">{business.city}</td>
                                <td className="px-3.5 py-3 text-xs">
                                    {business.instagram ? <div>IG</div> : null}
                                    {business.facebook ? <div>FB</div> : null}
                                    {business.website ? (
                                        <a className="font-bold text-brand-800" href={business.website}>
                                            web
                                        </a>
                                    ) : null}
                                </td>
                                <td className="px-3.5 py-3">
                                    <StatusBadge status={business.approved ? 'approved' : 'pending'} />
                                </td>
                                <td className="px-3.5 py-3 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        className="mr-3 cursor-pointer border-0 bg-transparent text-[13px] font-bold text-brand-900"
                                        onClick={() => setDetail(business)}
                                    >
                                        View
                                    </button>
                                    {business.approved ? (
                                        <button
                                            type="button"
                                            className="cursor-pointer rounded-full border border-red-200 bg-white px-3 py-1.5 text-[13px] font-bold text-danger transition hover:border-red-400"
                                            onClick={() => setPending({ business, approved: false })}
                                        >
                                            Revoke
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            className="cursor-pointer rounded-full bg-brand-800 px-3 py-1.5 text-[13px] font-bold text-white transition hover:bg-brand-900"
                                            onClick={() => setPending({ business, approved: true })}
                                        >
                                            Approve
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination
                page={businesses.current_page}
                lastPage={businesses.last_page}
                total={businesses.total}
            />

            <ConfirmDialog
                open={pending !== null}
                title={pending?.approved ? 'Approve this partner?' : 'Revoke this partner?'}
                message={
                    pending?.approved
                        ? 'The business owner will be able to publish packages and manage reservations.'
                        : 'The business owner will lose access to partner tools and their packages will no longer be manageable.'
                }
                confirmLabel={pending?.approved ? 'Approve partner' : 'Revoke partner'}
                danger={pending ? !pending.approved : false}
                busy={busy}
                onConfirm={() => pending && setApproved(pending.business.id, pending.approved)}
                onCancel={() => setPending(null)}
            />

            {detail ? (
                <div
                    className="fixed inset-0 z-120 flex items-start justify-center overflow-y-auto bg-[#0b1d36]/70 p-4 py-10"
                    role="dialog"
                    aria-modal="true"
                    aria-label={`Application from ${detail.name}`}
                    onClick={() => setDetail(null)}
                >
                    <div
                        className="w-full max-w-[720px] rounded-[20px] border border-line bg-white p-6 shadow-card"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-3xl">{detail.name}</h2>
                                <p className="text-muted">
                                    {detail.type_label} · {detail.city}
                                    {detail.district ? `, ${detail.district}` : ''}
                                </p>
                            </div>
                            <StatusBadge status={detail.approved ? 'approved' : 'pending'} />
                        </div>

                        {detail.cover_image ? (
                            <img
                                src={detail.cover_image}
                                alt=""
                                className="mb-4 h-44 w-full rounded-xl object-cover"
                            />
                        ) : null}

                        <dl className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt className="text-[11px] font-bold tracking-wide text-muted uppercase">
                                    Owner
                                </dt>
                                <dd className="text-sm">
                                    {detail.owner?.name ?? '—'}
                                    <br />
                                    <span className="text-muted">{detail.owner?.email ?? ''}</span>
                                    {detail.owner?.email_verified ? (
                                        <span className="block text-xs font-bold text-brand-800">
                                            Email verified
                                        </span>
                                    ) : (
                                        <span className="block text-xs font-bold text-warn">Email not verified</span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-[11px] font-bold tracking-wide text-muted uppercase">
                                    Mobile
                                </dt>
                                <dd className="text-sm">
                                    {detail.phone ?? detail.owner?.phone ?? '—'}
                                    {detail.phone_verified_at ? (
                                        <span className="block text-xs font-bold text-brand-800">
                                            Verified by SMS {formatDate(detail.phone_verified_at)}
                                        </span>
                                    ) : (
                                        <span className="block text-xs font-bold text-warn">Not verified</span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-[11px] font-bold tracking-wide text-muted uppercase">
                                    Submitted
                                </dt>
                                <dd className="text-sm">{formatDate(detail.created_at)}</dd>
                            </div>
                            <div>
                                <dt className="text-[11px] font-bold tracking-wide text-muted uppercase">
                                    Address
                                </dt>
                                <dd className="text-sm">{detail.address ?? '—'}</dd>
                            </div>
                        </dl>

                        <div className="mt-4">
                            <span className="text-[11px] font-bold tracking-wide text-muted uppercase">
                                About the business
                            </span>
                            <p className="text-sm whitespace-pre-line text-ink">
                                {detail.description || 'No description was provided.'}
                            </p>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            {([
                                ['Website', detail.website],
                                ['Instagram', detail.instagram],
                                ['Facebook', detail.facebook],
                                ['TikTok', detail.tiktok],
                                ['WhatsApp', detail.whatsapp],
                            ] as Array<[string, string | null]>).map(([label, value]) =>
                                value ? (
                                    <a
                                        key={label}
                                        href={label === 'WhatsApp' ? `https://wa.me/${value.replace(/[^0-9]/g, '')}` : value}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="rounded-full border border-line bg-white px-3 py-1.5 text-[13px] font-semibold"
                                    >
                                        {label}
                                    </a>
                                ) : null,
                            )}
                        </div>

                        <div className="mt-5 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                className="cursor-pointer rounded-full border border-line bg-white px-4.5 py-2.5 text-sm font-bold text-brand-900"
                                onClick={() => setDetail(null)}
                            >
                                Close
                            </button>
                            {detail.approved ? (
                                <button
                                    type="button"
                                    className="cursor-pointer rounded-full border border-red-200 bg-white px-4.5 py-2.5 text-sm font-bold text-danger"
                                    onClick={() => setPending({ business: detail, approved: false })}
                                >
                                    Revoke access
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    className="cursor-pointer rounded-full bg-brand-800 px-4.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900"
                                    onClick={() => setPending({ business: detail, approved: true })}
                                >
                                    Approve partner
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
};

AdminPartners.layout = withAppLayout;

export default AdminPartners;
