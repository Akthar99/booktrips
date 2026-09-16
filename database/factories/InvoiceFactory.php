<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Business;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'period' => now()->format('Y-m'),
            'amount_lkr' => fake()->numberBetween(1000, 30000),
            'status' => InvoiceStatus::Open,
            'lines' => [],
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
