<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\StorePackageRequest;
use App\Http\Requests\Partner\UpdatePackageRequest;
use App\Models\Business;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PartnerPackageController extends Controller
{
    /**
     * Show the create-package form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('partner/packages/form', [
            'package' => null,
            'categories' => config('booktrips.categories'),
            'categoryDefaults' => config('booktrips.category_defaults'),
            'business' => $this->businessContext($request->user()->business),
        ]);
    }

    /**
     * Publish a new package.
     */
    public function store(StorePackageRequest $request): RedirectResponse
    {
        $business = $request->user()->business;

        $attributes = $this->attributes($request, $business);

        // The slug is derived server side so it never comes from request input.
        $package = new Package($attributes);
        $package->slug = $this->uniqueSlug($attributes['title']);
        $package->save();

        return to_route('partner.dashboard')->with('success', 'Package published.');
    }

    /**
     * Show the edit-package form.
     */
    public function edit(Request $request, Package $package): Response
    {
        $this->authorize('update', $package);

        return Inertia::render('partner/packages/form', [
            'package' => $this->payload($package),
            'categories' => config('booktrips.categories'),
            'categoryDefaults' => config('booktrips.category_defaults'),
            'business' => $this->businessContext($request->user()->business),
        ]);
    }

    /**
     * Save changes to a package.
     */
    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->fill($this->attributes($request, $request->user()->business, $package))->save();

        return to_route('partner.dashboard')->with('success', 'Package saved.');
    }

    /**
     * Hide a package (soft deactivate — it can be re-listed later).
     */
    public function destroy(Package $package): RedirectResponse
    {
        $this->authorize('delete', $package);

        $package->forceFill(['active' => false])->save();

        return back()->with('success', 'Package hidden from search.');
    }

    /**
     * Put a hidden package back in the catalogue.
     */
    public function publish(Package $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->forceFill(['active' => true])->save();

        return back()->with('success', 'Package is live again.');
    }

    /**
     * Build the persisted attribute set from the validated request.
     *
     * @return array<string, mixed>
     */
    private function attributes(Request $request, Business $business, ?Package $existing = null): array
    {
        $title = (string) $request->input('title', $existing->title ?? '');
        $location = trim((string) $request->input('location', $existing->location ?? ''));

        return [
            'business_id' => $business->id,
            'title' => $title,
            'category' => (string) $request->input('category', $existing->category ?? 'dayout'),
            'description' => (string) $request->input('description', $existing->description ?? ''),
            'highlight' => $this->nullableString($request->input('highlight', $existing?->highlight)),
            'location' => $location,
            'address' => $this->nullableString($request->input('address', $existing?->address)) ?? $location,
            'district' => $this->nullableString($request->input('district', $existing?->district)) ?? $business->district,
            'lat' => $this->nullableFloat($request->input('lat', $existing?->lat)),
            'lng' => $this->nullableFloat($request->input('lng', $existing?->lng)),
            'schedule_type' => (string) $request->input('schedule_type', $existing?->schedule_type->value ?? 'always'),
            'schedule_start' => $this->nullableDate($request->input('schedule_start', $existing?->schedule_start?->toDateString())),
            'schedule_end' => $this->nullableDate($request->input('schedule_end', $existing?->schedule_end?->toDateString())),
            'weekdays' => $this->weekdays($request->input('weekdays', $existing?->weekdays)),
            'duration_days' => (int) $request->input('duration_days', $existing->duration_days ?? 1),
            'duration_nights' => (int) $request->input('duration_nights', $existing->duration_nights ?? 0),
            'price_lkr' => (int) $request->input('price_lkr', $existing->price_lkr ?? 0),
            'price_type' => (string) $request->input('price_type', $existing?->price_type->value ?? 'per_package'),
            ...$this->discount($request, $existing),
            'min_guests' => max((int) $request->input('min_guests', $existing->min_guests ?? 1), 1),
            'max_guests' => max((int) $request->input('max_guests', $existing->max_guests ?? 8), 1),
            'included' => $this->stringList($request->input('included', $existing?->included)),
            'excluded' => $this->stringList($request->input('excluded', $existing?->excluded)),
            'itinerary' => $this->itinerary($request->input('itinerary', $existing?->itinerary)),
            'amenities' => $this->stringList($request->input('amenities', $existing?->amenities)),
            'images' => $this->stringList($request->input('images', $existing?->images)),
            'meeting_point' => $this->nullableString($request->input('meeting_point', $existing?->meeting_point)),
            'cancellation_policy' => $this->nullableString($request->input('cancellation_policy', $existing?->cancellation_policy))
                ?? 'Free cancellation up to 48 hours before the start time.',
            'active' => (bool) $request->boolean('active', $existing->active ?? true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function discount(Request $request, ?Package $existing): array
    {
        $type = (string) $request->input('discount_type', $existing?->discount_type->value ?? 'none');
        $value = (float) $request->input('discount_value', $existing->discount_value ?? 0);

        if (! in_array($type, ['percentage', 'fixed'], true) || $value <= 0) {
            return [
                'discount_type' => 'none',
                'discount_value' => 0,
                'discount_enabled' => false,
                'discount_start' => null,
                'discount_end' => null,
            ];
        }

        return [
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_enabled' => (bool) $request->boolean('discount_enabled', $existing->discount_enabled ?? true),
            'discount_start' => $this->nullableDate($request->input('discount_start', $existing?->discount_start?->toDateString())),
            'discount_end' => $this->nullableDate($request->input('discount_end', $existing?->discount_end?->toDateString())),
        ];
    }

    /**
     * Data for the package form.
     *
     * @return array<string, mixed>
     */
    private function payload(Package $package): array
    {
        return [
            'id' => $package->id,
            'slug' => $package->slug,
            'title' => $package->title,
            'category' => $package->category,
            'description' => $package->description,
            'highlight' => $package->highlight,
            'location' => $package->location,
            'address' => $package->address,
            'district' => $package->district,
            'lat' => $package->lat,
            'lng' => $package->lng,
            'schedule_type' => $package->schedule_type->value,
            'schedule_start' => $package->schedule_start?->toDateString() ?? '',
            'schedule_end' => $package->schedule_end?->toDateString() ?? '',
            'weekdays' => $package->weekdays ?? [],
            'duration_days' => $package->duration_days,
            'duration_nights' => $package->duration_nights,
            'price_lkr' => $package->price_lkr,
            'price_type' => $package->price_type->value,
            'discount_type' => $package->discount_type->value,
            'discount_value' => $package->discount_value,
            'discount_enabled' => $package->discount_enabled,
            'discount_start' => $package->discount_start?->toDateString() ?? '',
            'discount_end' => $package->discount_end?->toDateString() ?? '',
            'min_guests' => $package->min_guests,
            'max_guests' => $package->max_guests,
            'included' => $package->included ?? [],
            'excluded' => $package->excluded ?? [],
            'itinerary' => $package->itinerary ?? [],
            'amenities' => $package->amenities ?? [],
            'images' => $package->images ?? [],
            'meeting_point' => $package->meeting_point,
            'cancellation_policy' => $package->cancellation_policy,
            'active' => $package->active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function businessContext(Business $business): array
    {
        return [
            'name' => $business->name,
            'city' => $business->city,
            'district' => $business->district,
            'type' => $business->type->value,
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'package';
        $base = mb_substr($base, 0, 60);
        $slug = $base;
        $suffix = 1;

        while (Package::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableDate(mixed $value): ?string
    {
        return $this->nullableString($value);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    /**
     * @return array<int, int>
     */
    private function weekdays(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item): string => trim((string) $item),
            $value,
        ), fn (string $item): bool => $item !== ''));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function itinerary(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(fn (array $day): array => [
            'day' => (int) ($day['day'] ?? 1),
            'title' => trim((string) ($day['title'] ?? '')),
            'description' => trim((string) ($day['description'] ?? '')),
        ], array_filter($value, 'is_array')));
    }
}
