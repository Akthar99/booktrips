<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Enums\ScheduleType;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property string $title
 * @property string $slug
 * @property string $category
 * @property string $description
 * @property string|null $highlight
 * @property string $location
 * @property string|null $address
 * @property string|null $district
 * @property float|null $lat
 * @property float|null $lng
 * @property ScheduleType $schedule_type
 * @property Carbon|null $schedule_start
 * @property Carbon|null $schedule_end
 * @property array<int, int>|null $weekdays
 * @property int $duration_days
 * @property int $duration_nights
 * @property int $price_lkr
 * @property PriceType $price_type
 * @property DiscountType $discount_type
 * @property float $discount_value
 * @property bool $discount_enabled
 * @property Carbon|null $discount_start
 * @property Carbon|null $discount_end
 * @property int $min_guests
 * @property int $max_guests
 * @property array<int, string>|null $included
 * @property array<int, string>|null $excluded
 * @property array<int, array<string, mixed>>|null $itinerary
 * @property array<int, string>|null $amenities
 * @property array<int, string>|null $images
 * @property string|null $meeting_point
 * @property string|null $cancellation_policy
 * @property float $rating
 * @property int $review_count
 * @property bool $featured
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'business_id', 'title', 'category', 'description', 'highlight', 'location',
    'address', 'district', 'lat', 'lng', 'schedule_type', 'schedule_start',
    'schedule_end', 'weekdays', 'duration_days', 'duration_nights', 'price_lkr',
    'price_type', 'discount_type', 'discount_value', 'discount_enabled',
    'discount_start', 'discount_end', 'min_guests', 'max_guests', 'included',
    'excluded', 'itinerary', 'amenities', 'images', 'meeting_point',
    'cancellation_policy', 'active',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schedule_type' => ScheduleType::class,
            'schedule_start' => 'date:Y-m-d',
            'schedule_end' => 'date:Y-m-d',
            'weekdays' => 'array',
            'duration_days' => 'integer',
            'duration_nights' => 'integer',
            'price_lkr' => 'integer',
            'price_type' => PriceType::class,
            'discount_type' => DiscountType::class,
            'discount_value' => 'float',
            'discount_enabled' => 'boolean',
            'discount_start' => 'date:Y-m-d',
            'discount_end' => 'date:Y-m-d',
            'min_guests' => 'integer',
            'max_guests' => 'integer',
            'included' => 'array',
            'excluded' => 'array',
            'itinerary' => 'array',
            'amenities' => 'array',
            'images' => 'array',
            'lat' => 'float',
            'lng' => 'float',
            'rating' => 'float',
            'review_count' => 'integer',
            'featured' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @param  Builder<Package>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @param  Builder<Package>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('featured', true);
    }
}
