import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
    store as packageStore,
    update as packageUpdate,
} from '@/actions/App/Http/Controllers/Partner/PartnerPackageController';
import { store as imageStore } from '@/actions/App/Http/Controllers/Partner/PartnerImageController';
import Alert from '@/components/booktrips/alert';
import Button from '@/components/booktrips/button';
import ChipList from '@/components/booktrips/chip-list';
import DateInput from '@/components/booktrips/date-input';
import { Field, Input, Select, Textarea } from '@/components/booktrips/field';
import LocationPicker from '@/components/booktrips/location-picker';
import SharePackage from '@/components/booktrips/share-package';
import { withAppLayout } from '@/layouts/app-layout';
import { WEEKDAYS } from '@/lib/booktrips';
import { cn } from '@/lib/utils';
import type { InertiaComponent } from '@/types/inertia';

type CategoryDefault = { included: string[]; excluded: string[] };

type ItineraryDay = { day: number; title: string; description: string };

type PackagePayload = {
    id?: number;
    slug?: string | null;
    title: string;
    category: string;
    description: string;
    highlight: string | null;
    location: string;
    address: string | null;
    district: string | null;
    lat: number | null;
    lng: number | null;
    schedule_type: string;
    schedule_start: string;
    schedule_end: string;
    weekdays: number[];
    duration_days: number;
    duration_nights: number;
    price_lkr: number | '';
    price_type: string;
    discount_type: string;
    discount_value: number;
    discount_enabled: boolean;
    discount_start: string;
    discount_end: string;
    min_guests: number;
    max_guests: number;
    included: string[];
    excluded: string[];
    itinerary: ItineraryDay[];
    amenities: string[];
    images: string[];
    meeting_point: string | null;
    cancellation_policy: string | null;
    active: boolean;
};

type FormProps = {
    package: PackagePayload | null;
    categories: Array<{ slug: string; name: string }>;
    categoryDefaults: Record<string, CategoryDefault>;
    business: {
        name: string;
        city: string;
        district: string | null;
        type: string;
    };
};

function defaultForm(business: FormProps['business']): PackagePayload {
    return {
        title: '',
        category: 'dayout',
        description: '',
        highlight: '',
        location: business.city,
        address: business.city,
        district: business.district,
        lat: null,
        lng: null,
        schedule_type: 'always',
        schedule_start: '',
        schedule_end: '',
        weekdays: [0, 1, 2, 3, 4, 5, 6],
        duration_days: 1,
        duration_nights: 0,
        price_lkr: '',
        price_type: 'per_package',
        discount_type: 'none',
        discount_value: 0,
        discount_enabled: false,
        discount_start: '',
        discount_end: '',
        min_guests: 1,
        max_guests: 8,
        included: [],
        excluded: [],
        itinerary: [],
        amenities: [],
        images: [],
        meeting_point: '',
        cancellation_policy:
            'Free cancellation up to 48 hours before the start time.',
        active: true,
    };
}

const PackageForm: InertiaComponent<FormProps> = ({
    package: existing,
    categories,
    categoryDefaults,
    business,
}) => {
    const editing = Boolean(existing?.id);
    const [uploadError, setUploadError] = useState('');
    const [uploading, setUploading] = useState(false);

    const form = useForm<PackagePayload>(existing ?? defaultForm(business));

    function set<K extends keyof PackagePayload>(
        key: K,
        value: PackagePayload[K],
    ) {
        form.setData((data) => ({ ...data, [key]: value }));
    }

    function onCategory(slug: string) {
        const defaults = categoryDefaults[slug] ?? {
            included: [],
            excluded: [],
        };
        form.setData((data) => ({
            ...data,
            category: slug,
            included: [...(defaults.included ?? [])],
            excluded: [...(defaults.excluded ?? [])],
        }));
    }

    const MAX_PHOTO_MB = 6;
    const MAX_PHOTOS = 25;
    const MAX_BATCH_MB = 12;

    /**
     * Photos are uploaded in small batches so a 25-photo package stays under
     * PHP's post_max_size, each photo appears as soon as it lands, and the
     * watermark pass per request stays short.
     */
    function uploadBatches(files: File[]): File[][] {
        const batches: File[][] = [];
        let batch: File[] = [];
        let size = 0;

        for (const file of files) {
            if (
                batch.length >= 6 ||
                size + file.size > MAX_BATCH_MB * 1024 * 1024
            ) {
                if (batch.length) {
                    batches.push(batch);
                }

                batch = [];
                size = 0;
            }

            batch.push(file);
            size += file.size;
        }

        if (batch.length) {
            batches.push(batch);
        }

        return batches;
    }

    async function onFiles(event: React.ChangeEvent<HTMLInputElement>) {
        const selected = Array.from(event.target.files ?? []);
        const remaining = MAX_PHOTOS - (form.data.images?.length ?? 0);
        const files = selected.slice(0, Math.max(remaining, 0));

        event.target.value = '';

        if (!files.length) {
            if (selected.length) {
                setUploadError(
                    `You can have ${MAX_PHOTOS} photos per package at most.`,
                );
            }

            return;
        }

        const tooBig = files.find(
            (file) => file.size > MAX_PHOTO_MB * 1024 * 1024,
        );

        if (tooBig) {
            setUploadError(
                `${tooBig.name} is larger than ${MAX_PHOTO_MB} MB. Please resize it and try again.`,
            );

            return;
        }

        setUploading(true);
        setUploadError('');

        const appendImages = (urls: string[]) =>
            form.setData((data) => ({
                ...data,
                images: [...(data.images ?? []), ...urls],
            }));

        try {
            for (const batch of uploadBatches(files)) {
                const body = new FormData();
                batch.forEach((file) => body.append('images[]', file));

                const response = await fetch(imageStore.url(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': decodeURIComponent(
                            document.cookie
                                .split('; ')
                                .find((row) => row.startsWith('XSRF-TOKEN='))
                                ?.split('=')[1] ?? '',
                        ),
                    },
                    body,
                });

                const data = (await response.json().catch(() => ({}))) as {
                    images?: string[];
                    message?: string;
                    errors?: Record<string, string[]>;
                };

                if (!response.ok) {
                    setUploadError(
                        data.message ??
                            Object.values(data.errors ?? {}).flat()[0] ??
                            'Upload failed.',
                    );

                    break;
                }

                appendImages(data.images ?? []);
            }
        } catch {
            setUploadError('Upload failed. Please try again.');
        } finally {
            setUploading(false);
        }
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            price_lkr: Number(data.price_lkr),
        }));

        if (editing && existing?.id) {
            form.put(packageUpdate.url(existing.id));

            return;
        }

        form.post(packageStore.url());
    }

    const firstError = Object.values(form.errors as Record<string, string>)[0];
    const images = form.data.images ?? [];

    return (
        <div className="mx-auto w-[min(720px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">
                {editing ? 'Edit package' : 'New package'}
            </h1>
            {firstError ? <Alert tone="error">{firstError}</Alert> : null}
            {uploadError ? <Alert tone="error">{uploadError}</Alert> : null}
            <form
                onSubmit={submit}
                className="rounded-card border-line mt-4 border bg-white p-6"
                noValidate
            >
                <Field label="Title">
                    <Input
                        required
                        value={form.data.title}
                        onChange={(event) => set('title', event.target.value)}
                    />
                </Field>
                <Field label="Category">
                    <Select
                        value={form.data.category}
                        onChange={(event) => onCategory(event.target.value)}
                    >
                        {categories.map((category) => (
                            <option key={category.slug} value={category.slug}>
                                {category.name}
                            </option>
                        ))}
                    </Select>
                </Field>
                <p className="text-muted -mt-1.5 mb-3 text-[13px]">
                    Changing category loads typical includes and excludes. Add
                    or remove with ×.
                </p>
                <Field label="Short highlight">
                    <Input
                        value={form.data.highlight ?? ''}
                        onChange={(event) =>
                            set('highlight', event.target.value)
                        }
                    />
                </Field>
                <Field label="Description">
                    <Textarea
                        required
                        value={form.data.description}
                        onChange={(event) =>
                            set('description', event.target.value)
                        }
                    />
                </Field>

                <LocationPicker
                    value={{
                        location: form.data.location,
                        address: form.data.address ?? '',
                        lat: form.data.lat,
                        lng: form.data.lng,
                    }}
                    onChange={(patch) =>
                        form.setData((data) => ({
                            ...data,
                            location: patch.location,
                            address: patch.address,
                            lat: patch.lat || null,
                            lng: patch.lng || null,
                        }))
                    }
                />

                <div className="grid gap-3 sm:grid-cols-3">
                    <Field label="Days">
                        <Input
                            type="number"
                            min={1}
                            value={form.data.duration_days}
                            onChange={(event) =>
                                set('duration_days', Number(event.target.value))
                            }
                        />
                    </Field>
                    <Field label="Nights">
                        <Input
                            type="number"
                            min={0}
                            value={form.data.duration_nights}
                            onChange={(event) =>
                                set(
                                    'duration_nights',
                                    Number(event.target.value),
                                )
                            }
                        />
                    </Field>
                    <Field label="Price (LKR)">
                        <Input
                            type="number"
                            required
                            min={1}
                            value={form.data.price_lkr}
                            onChange={(event) =>
                                set(
                                    'price_lkr',
                                    event.target.value === ''
                                        ? ''
                                        : Number(event.target.value),
                                )
                            }
                        />
                    </Field>
                </div>
                <Field label="Price type">
                    <Select
                        value={form.data.price_type}
                        onChange={(event) =>
                            set('price_type', event.target.value)
                        }
                    >
                        <option value="per_package">Per package</option>
                        <option value="per_person">Per person</option>
                        <option value="per_night">
                            Per night (villa/hotel)
                        </option>
                    </Select>
                </Field>

                <div className="border-brand-100 bg-brand-50 my-4.5 rounded-[14px] border p-3.5">
                    <h3 className="mb-1 font-sans text-base font-bold">
                        Discount (optional)
                    </h3>
                    <p className="text-muted mb-3 text-[13px]">
                        Offer a percentage or fixed LKR discount on this
                        package. Dates are optional.
                    </p>
                    <Field label="Discount type">
                        <Select
                            value={form.data.discount_type}
                            onChange={(event) => {
                                const type = event.target.value;
                                form.setData((data) => ({
                                    ...data,
                                    discount_type: type,
                                    discount_value:
                                        type === 'none'
                                            ? 0
                                            : data.discount_value,
                                    discount_enabled: type !== 'none',
                                }));
                            }}
                        >
                            <option value="none">No discount</option>
                            <option value="percentage">Percentage off</option>
                            <option value="fixed">
                                Fixed amount off (LKR)
                            </option>
                        </Select>
                    </Field>
                    {form.data.discount_type !== 'none' ? (
                        <>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <Field
                                    label={
                                        form.data.discount_type === 'percentage'
                                            ? 'Discount %'
                                            : 'Discount amount (LKR)'
                                    }
                                >
                                    <Input
                                        type="number"
                                        min={0}
                                        max={
                                            form.data.discount_type ===
                                            'percentage'
                                                ? 100
                                                : undefined
                                        }
                                        value={form.data.discount_value}
                                        onChange={(event) =>
                                            set(
                                                'discount_value',
                                                Number(event.target.value),
                                            )
                                        }
                                    />
                                </Field>
                                <label className="text-brand-900 flex cursor-pointer items-center gap-2 self-center text-[13px] font-bold">
                                    <input
                                        type="checkbox"
                                        className="accent-brand-800 h-4 w-4"
                                        checked={form.data.discount_enabled}
                                        onChange={(event) =>
                                            set(
                                                'discount_enabled',
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    Discount is active
                                </label>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <DateInput
                                    label="Discount from (optional)"
                                    value={form.data.discount_start}
                                    onChange={(value) =>
                                        set('discount_start', value)
                                    }
                                />
                                <DateInput
                                    label="Discount until (optional)"
                                    value={form.data.discount_end}
                                    min={form.data.discount_start || undefined}
                                    onChange={(value) =>
                                        set('discount_end', value)
                                    }
                                />
                            </div>
                        </>
                    ) : null}
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Min guests">
                        <Input
                            type="number"
                            min={1}
                            value={form.data.min_guests}
                            onChange={(event) =>
                                set('min_guests', Number(event.target.value))
                            }
                        />
                    </Field>
                    <Field label="Max guests">
                        <Input
                            type="number"
                            min={1}
                            value={form.data.max_guests}
                            onChange={(event) =>
                                set('max_guests', Number(event.target.value))
                            }
                        />
                    </Field>
                </div>

                <h3 className="mt-2 mb-2 font-sans text-lg font-bold">
                    When it runs
                </h3>
                <Field label="Availability">
                    <Select
                        value={form.data.schedule_type}
                        onChange={(event) =>
                            set('schedule_type', event.target.value)
                        }
                    >
                        <option value="always">
                            No end date — keep running
                        </option>
                        <option value="range">Only in a date window</option>
                    </Select>
                </Field>
                {form.data.schedule_type === 'range' ? (
                    <div className="grid gap-3 sm:grid-cols-2">
                        <DateInput
                            label="From"
                            value={form.data.schedule_start}
                            onChange={(value) => set('schedule_start', value)}
                        />
                        <DateInput
                            label="Until"
                            value={form.data.schedule_end}
                            min={form.data.schedule_start || undefined}
                            onChange={(value) => set('schedule_end', value)}
                        />
                    </div>
                ) : null}
                <div className="mb-3 flex flex-col gap-1.5">
                    <span className="text-muted text-xs font-bold tracking-wide uppercase">
                        Days of week
                    </span>
                    <div className="flex flex-wrap gap-2">
                        {WEEKDAYS.map((day) => (
                            <button
                                key={day.n}
                                type="button"
                                className={cn(
                                    'border-line inline-flex cursor-pointer items-center gap-1.5 rounded-full border bg-white px-2.5 py-1.5 text-[13px] font-semibold',
                                    form.data.weekdays.includes(day.n) &&
                                        'border-brand-800 bg-brand-800 text-white',
                                )}
                                onClick={() =>
                                    set(
                                        'weekdays',
                                        form.data.weekdays.includes(day.n)
                                            ? form.data.weekdays.filter(
                                                  (value) => value !== day.n,
                                              )
                                            : [
                                                  ...form.data.weekdays,
                                                  day.n,
                                              ].sort(),
                                    )
                                }
                            >
                                {day.label}
                            </button>
                        ))}
                    </div>
                    <em className="text-muted text-xs not-italic">
                        Example: only Sat and Sun between 12 Sep and 30 Sep.
                    </em>
                </div>

                <ChipList
                    label="Included"
                    items={form.data.included}
                    onChange={(items) => set('included', items)}
                    placeholder="e.g. Breakfast"
                />
                <ChipList
                    label="Excluded"
                    items={form.data.excluded}
                    onChange={(items) => set('excluded', items)}
                    placeholder="e.g. Park tickets"
                />

                <div className="mb-3 flex flex-col gap-1.5">
                    <span className="text-muted text-xs font-bold tracking-wide uppercase">
                        Plan by day
                    </span>
                    <em className="text-muted text-xs not-italic">
                        Travellers see this as a day-by-day itinerary. Leave it
                        empty for simple stays.
                    </em>
                    {(form.data.itinerary ?? []).map((stop, position) => (
                        <div
                            key={position}
                            className="border-line rounded-xl border bg-white p-3"
                        >
                            <div className="mb-2 flex items-center justify-between gap-2">
                                <strong className="text-[13px]">
                                    Day {position + 1}
                                </strong>
                                <button
                                    type="button"
                                    className="text-danger cursor-pointer border-0 bg-transparent p-0 text-[13px] font-bold"
                                    onClick={() =>
                                        set(
                                            'itinerary',
                                            form.data.itinerary.filter(
                                                (_, index) =>
                                                    index !== position,
                                            ),
                                        )
                                    }
                                >
                                    Remove
                                </button>
                            </div>
                            <Input
                                placeholder="Morning: Sigiriya rock climb"
                                value={stop.title}
                                onChange={(event) =>
                                    set(
                                        'itinerary',
                                        form.data.itinerary.map((day, index) =>
                                            index === position
                                                ? {
                                                      ...day,
                                                      title: event.target.value,
                                                  }
                                                : day,
                                        ),
                                    )
                                }
                            />
                            <div className="mt-2">
                                <Textarea
                                    placeholder="What happens, what to bring, timings…"
                                    value={stop.description}
                                    onChange={(event) =>
                                        set(
                                            'itinerary',
                                            form.data.itinerary.map(
                                                (day, index) =>
                                                    index === position
                                                        ? {
                                                              ...day,
                                                              description:
                                                                  event.target
                                                                      .value,
                                                          }
                                                        : day,
                                            ),
                                        )
                                    }
                                />
                            </div>
                        </div>
                    ))}
                    <button
                        type="button"
                        className="border-line text-brand-900 hover:border-brand-700 inline-flex w-fit cursor-pointer items-center gap-1.5 rounded-full border bg-white px-3.5 py-2 text-[13px] font-bold transition"
                        onClick={() =>
                            set('itinerary', [
                                ...(form.data.itinerary ?? []),
                                {
                                    day: (form.data.itinerary ?? []).length + 1,
                                    title: '',
                                    description: '',
                                },
                            ])
                        }
                    >
                        + Add day
                    </button>
                </div>

                <div className="mb-3 flex flex-col gap-1.5">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-muted text-xs font-bold tracking-wide uppercase">
                            Photos
                        </span>
                        <span
                            className={cn(
                                'text-xs font-bold',
                                images.length >= 5
                                    ? 'text-brand-800'
                                    : 'text-warn',
                            )}
                        >
                            {images.length} of {MAX_PHOTOS} photos
                        </span>
                    </div>
                    <div className="bg-cream-dark h-1.5 w-full overflow-hidden rounded-full">
                        <div
                            className={cn(
                                'h-full rounded-full transition-all',
                                images.length >= 5 ? 'bg-brand-700' : 'bg-warn',
                            )}
                            style={{
                                width: `${Math.min(100, (images.length / MAX_PHOTOS) * 100)}%`,
                            }}
                        />
                    </div>
                    <em className="text-muted text-xs not-italic">
                        Up to {MAX_PHOTOS} photos, {MAX_PHOTO_MB} MB each. Each
                        photo is watermarked before it is stored, so use photos
                        you are happy to brand.
                    </em>
                    {images.length < 4 ? (
                        <p className="text-warn rounded-lg bg-orange-50 px-2.5 py-2 text-[12px] font-semibold">
                            Listings with 5 or more photos get noticeably more
                            bookings. Aim for a mix of the place, the view, food
                            and what guests actually do — at least 4 to start.
                        </p>
                    ) : images.length < 5 ? (
                        <p className="text-brand-800 text-[12px] font-semibold">
                            Nice — one more photo and you are in the sweet spot.
                        </p>
                    ) : (
                        <p className="text-brand-800 text-[12px] font-semibold">
                            Great coverage. This listing will stand out in
                            search.
                        </p>
                    )}
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        onChange={onFiles}
                        className="mt-1 text-sm"
                    />
                    {uploading ? (
                        <em className="text-muted text-xs not-italic">
                            Uploading…
                        </em>
                    ) : null}
                    <div className="mt-2.5 flex flex-wrap gap-2">
                        {images.map((src) => (
                            <div
                                key={src}
                                className="relative h-18 w-22 overflow-hidden rounded-[10px]"
                            >
                                <img
                                    src={src}
                                    alt=""
                                    className="h-full w-full object-cover"
                                />
                                <button
                                    type="button"
                                    className="absolute top-1 right-1 grid h-5.5 w-5.5 cursor-pointer place-items-center rounded-full border-0 bg-white font-extrabold"
                                    onClick={() =>
                                        set(
                                            'images',
                                            images.filter(
                                                (value) => value !== src,
                                            ),
                                        )
                                    }
                                >
                                    ×
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                <Field label="Meeting point (optional)">
                    <Input
                        value={form.data.meeting_point ?? ''}
                        placeholder="Only if guests meet you somewhere other than the stay"
                        onChange={(event) =>
                            set('meeting_point', event.target.value)
                        }
                    />
                </Field>
                <p className="text-muted -mt-2 mb-4 text-[13px]">
                    Skip this for hotels and villas. Use it for hikes, rafting
                    and day outs.
                </p>
                <Field label="Cancellation policy">
                    <Textarea
                        value={form.data.cancellation_policy ?? ''}
                        onChange={(event) =>
                            set('cancellation_policy', event.target.value)
                        }
                    />
                </Field>

                <label className="mb-4 flex items-center gap-2.5 text-sm font-semibold">
                    <input
                        type="checkbox"
                        className="h-4 w-4"
                        checked={form.data.active}
                        onChange={(event) =>
                            set('active', event.target.checked)
                        }
                    />
                    List this package in search
                </label>

                {editing && existing?.id ? (
                    <div className="bg-cream mb-3 flex items-center gap-3 rounded-xl px-3.5 py-3">
                        <span className="text-brand-900 text-[13px] font-bold">
                            Share this package
                        </span>
                        <SharePackage
                            id={existing.id}
                            slug={existing.slug ?? null}
                            title={form.data.title}
                        />
                    </div>
                ) : null}

                <Button type="submit" disabled={form.processing}>
                    {form.processing
                        ? 'Saving…'
                        : editing
                          ? 'Save'
                          : 'Publish package'}
                </Button>
            </form>
        </div>
    );
};

PackageForm.layout = withAppLayout;

export default PackageForm;
