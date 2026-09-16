<?php

namespace App\Presenters;

use App\Models\Business;
use App\Models\Package;
use App\Services\PricingService;

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
