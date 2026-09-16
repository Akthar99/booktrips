<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\DiscountType;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_code' => 'BT-'.strtoupper(Str::random(6)),
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'business_id' => fn (array $attributes): int => (int) DB::table('packages')->where('id', $attributes['package_id'])->value('business_id'),
            'check_in' => now()->addWeek()->toDateString(),
            'check_out' => now()->addWeek()->toDateString(),
            'guests' => 2,
            'base_total_lkr' => 10000,
            'discount_lkr' => 0,
            'discount_applied' => false,
            'discount_type' => DiscountType::None,
            'discount_value' => 0,
            'total_lkr' => 10000,
            'status' => BookingStatus::Requested,
            'payment_method' => 'pay_at_destination',
            'guest_name' => fake()->name(),
            'guest_phone' => fake()->numerify('07########'),
            'notes' => null,
            'commission_added' => false,
            'commission_lkr' => 0,
            'escalated' => false,
        ];
    }

    /**
     * Attach the booking to an existing package and keep money fields consistent.
     */
    public function forPackage(Package $package): static
    {
        return $this->state(fn (array $attributes) => [
            'package_id' => $package->id,
            'business_id' => $package->business_id,
            'base_total_lkr' => $package->price_lkr,
            'total_lkr' => $package->price_lkr,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Completed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
        ]);
    }

    /**
     * An old unanswered request, eligible for escalation.
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subHours(30),
            'updated_at' => now()->subHours(30),
        ]);
    }
}
