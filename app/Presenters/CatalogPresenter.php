<?php

namespace App\Presenters;

use App\Models\Business;
use App\Models\Package;
use App\Services\PricingService;
use Illuminate\Support\Str;

class CatalogPresenter
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Shape a package for listing cards.
     *
     * @return array<string, mixed>
     */
    public function packageCard(Package $package): array
    {
        return [
            'id' => $package->id,
            'title' => $package->title,
            'slug' => $package->slug,
            'category' => $package->category,
            'highlight' => $package->highlight,
            'location' => $package->location,
            'district' => $package->district,
            'images' => $package->images ?? [],
            'price_lkr' => $package->price_lkr,
            'price_type' => $package->price_type->value,
            'duration_days' => $package->duration_days,
            'duration_nights' => $package->duration_nights,
            'rating' => $package->rating,
            'review_count' => $package->review_count,
            'featured' => $package->featured,
            'active' => $package->active,
            ...$this->pricing->pricingFor($package),
            'host' => $package->business ? $this->hostSummary($package->business) : null,
        ];
    }

    /**
     * Shape a package for its detail page.
     *
     * @return array<string, mixed>
     */
    public function packageDetail(Package $package): array
    {
        return [
            ...$this->packageCard($package),
            'business_id' => $package->business_id,
            'description' => $package->description,
            'address' => $package->address,
            'lat' => $package->lat,
            'lng' => $package->lng,
            'schedule_type' => $package->schedule_type->value,
            'schedule_start' => $package->schedule_start?->toDateString(),
            'schedule_end' => $package->schedule_end?->toDateString(),
            'weekdays' => $package->weekdays ?? [],
            'min_guests' => $package->min_guests,
            'max_guests' => $package->max_guests,
            'included' => $package->included ?? [],
            'excluded' => $package->excluded ?? [],
            'itinerary' => $package->itinerary ?? [],
            'amenities' => $package->amenities ?? [],
            'meeting_point' => $package->meeting_point,
            'cancellation_policy' => $package->cancellation_policy,
        ];
    }

    /**
     * Unique head tags for the public package page — rendered by SSR before
     * the browser sees the document.
     *
     * @return array{title: string, description: string, canonical: string, image: string|null}
     */
    public function packageSeo(Package $package): array
    {
        $categoryNames = array_column(config('booktrips.categories'), 'name', 'slug');
        $category = (string) ($categoryNames[$package->category] ?? Str::headline((string) $package->category));

        $duration = $package->duration_days >= 2 ? $package->duration_days.'-day' : 'full-day';

        $location = trim((string) $package->location);
        $title = $location !== '' && ! Str::contains(Str::lower((string) $package->title), Str::lower("in {$location}"))
            ? "{$package->title} in {$location} | Book Trips Sri Lanka"
            : "{$package->title} | Book Trips Sri Lanka";

        $snippet = (string) Str::of((string) ($package->highlight ?: $package->description))
            ->squish()
            ->words(14, '')
            ->trim();

        $description = Str::limit(implode(' ', array_filter([
            "Book {$package->title} — a {$duration} ".Str::lower($category).' experience in '
                .$package->location.($package->district ? ", {$package->district}" : '').'.',
            $snippet === '' ? 'Verified local hosts and instant booking.' : Str::finish($snippet, '.'),
            'From LKR '.number_format($package->price_lkr).' — pay at the destination.',
        ])), 155);

        $images = $package->images ?? [];
        $image = $images[0] ?? ($package->relationLoaded('business') ? $package->business?->cover_image : null);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => route('packages.show', $package->slug),
            'image' => $image,
        ];
    }

    /**
     * Public summary of a host business.
     *
     * @return array<string, mixed>
     */
    public function hostSummary(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'type' => $business->type->value,
            'city' => $business->city,
            'cover_image' => $business->cover_image,
        ];
    }
}
