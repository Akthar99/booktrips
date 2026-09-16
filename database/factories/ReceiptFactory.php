<?php

namespace Database\Factories;

use App\Enums\ReceiptStatus;
use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'business_id' => fn (array $attributes): int => (int) DB::table('invoices')->where('id', $attributes['invoice_id'])->value('business_id'),
            'file_path' => 'receipts/test/'.Str::random(10).'.jpg',
            'original_name' => 'transfer-receipt.jpg',
            'note' => null,
            'status' => ReceiptStatus::Pending,
            'reviewed_at' => null,
        ];
    }
}
