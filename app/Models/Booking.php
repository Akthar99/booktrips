<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\DiscountType;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $booking_code
 * @property int $user_id
 * @property int $package_id
 * @property int $business_id
 * @property Carbon $check_in
 * @property Carbon $check_out
 * @property int $guests
 * @property int $base_total_lkr
 * @property int $discount_lkr
 * @property bool $discount_applied
 * @property DiscountType $discount_type
 * @property float $discount_value
 * @property int $total_lkr
 * @property BookingStatus $status
 * @property string $payment_method
 * @property string $guest_name
 * @property string $guest_phone
 * @property string|null $notes
 * @property bool $commission_added
 * @property int $commission_lkr
 * @property bool $escalated
 * @property Carbon|null $escalated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'booking_code', 'user_id', 'package_id', 'business_id', 'check_in', 'check_out',
    'guests', 'base_total_lkr', 'discount_lkr', 'discount_applied', 'discount_type',
    'discount_value', 'total_lkr', 'status', 'payment_method', 'guest_name',
    'guest_phone', 'notes', 'commission_added', 'commission_lkr', 'escalated', 'escalated_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'guests' => 'integer',
            'base_total_lkr' => 'integer',
            'discount_lkr' => 'integer',
            'discount_applied' => 'boolean',
            'discount_type' => DiscountType::class,
            'discount_value' => 'float',
            'total_lkr' => 'integer',
            'status' => BookingStatus::class,
            'commission_added' => 'boolean',
            'commission_lkr' => 'integer',
            'escalated' => 'boolean',
            'escalated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasOne<Review, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * @return HasMany<Dispute, $this>
     */
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    /**
     * Bookings that overlap the given date range (same package, capacity holding).
     *
     * Boundaries are inclusive on purpose: a day trip starts and ends on the same
     * date, and a group checking out still shares the day with the next group in.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeOverlapping(Builder $query, string $checkIn, string $checkOut): void
    {
        $query->where('check_in', '<=', $checkOut)
            ->where('check_out', '>=', $checkIn)
            ->whereIn('status', BookingStatus::holdingCapacity());
    }
}
