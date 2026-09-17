<?php

namespace App\Models;

use App\Enums\DisputePenalty;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A report raised by one side of a booking against the other.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $business_id
 * @property int $raised_by_user_id
 * @property int $against_user_id
 * @property DisputeType $type
 * @property DisputeStatus $status
 * @property string $summary
 * @property string|null $details
 * @property array<int, string>|null $evidence
 * @property string|null $response
 * @property array<int, string>|null $response_evidence
 * @property Carbon|null $responded_at
 * @property Carbon|null $response_deadline_at
 * @property DisputeResolution|null $resolution
 * @property DisputePenalty|null $penalty
 * @property int|null $penalty_amount_lkr
 * @property string|null $resolution_note
 * @property int|null $resolved_by_user_id
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'booking_id', 'business_id', 'raised_by_user_id', 'against_user_id', 'type',
    'status', 'summary', 'details', 'evidence', 'response', 'response_evidence',
    'responded_at', 'response_deadline_at', 'resolution', 'penalty',
    'penalty_amount_lkr', 'resolution_note', 'resolved_by_user_id', 'resolved_at',
])]
class Dispute extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DisputeType::class,
            'status' => DisputeStatus::class,
            'resolution' => DisputeResolution::class,
            'penalty' => DisputePenalty::class,
            'evidence' => 'array',
            'response_evidence' => 'array',
            'responded_at' => 'datetime',
            'response_deadline_at' => 'datetime',
            'resolved_at' => 'datetime',
            'penalty_amount_lkr' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function against(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function hasResponse(): bool
    {
        return $this->responded_at !== null;
    }

    /**
     * A verdict may only land after the other side had their chance to speak,
     * or sooner if they already answered.
     */
    public function canBeResolved(): bool
    {
        return $this->isOpen()
            && ($this->hasResponse() || $this->response_deadline_at === null || $this->response_deadline_at->isPast());
    }

    /**
     * @param  Builder<Dispute>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', DisputeStatus::Resolved);
    }
}
