<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use Database\Factories\ReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $business_id
 * @property string $file_path
 * @property string|null $original_name
 * @property string|null $note
 * @property ReceiptStatus $status
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['invoice_id', 'business_id', 'file_path', 'original_name', 'note', 'status', 'reviewed_at'])]
class Receipt extends Model
{
    /** @use HasFactory<ReceiptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReceiptStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
