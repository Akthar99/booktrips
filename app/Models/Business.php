<?php

namespace App\Models;

use App\Enums\BusinessType;
use App\Services\MediaUrl;
use Carbon\CarbonImmutable;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property BusinessType $type
 * @property string|null $description
 * @property string|null $address
 * @property string $city
 * @property string|null $district
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $cover_image
 * @property string|null $instagram
 * @property string|null $facebook
 * @property string|null $tiktok
 * @property string|null $whatsapp
 * @property bool $approved
 * @property int $strikes
 * @property CarbonImmutable|null $phone_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'type', 'description', 'address', 'city', 'district', 'phone',
    'email', 'website', 'cover_image', 'instagram', 'facebook', 'tiktok', 'whatsapp',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BusinessType::class,
            'approved' => 'boolean',
            'phone_verified_at' => 'datetime',
        ];
    }

    /**
     * Cover images resolve to browsable URLs (legacy local paths included).
     *
     * @return Attribute<string|null, string|null>
     */
    protected function coverImage(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null || $value === ''
                ? null
                : app(MediaUrl::class)->url($value),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Package, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
