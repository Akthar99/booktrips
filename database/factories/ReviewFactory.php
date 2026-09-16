<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            'package_id' => fn (array $attributes): int => (int) DB::table('bookings')->where('id', $attributes['booking_id'])->value('package_id'),
            'user_id' => fn (array $attributes): int => (int) DB::table('bookings')->where('id', $attributes['booking_id'])->value('user_id'),
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->sentence(4),
            'comment' => fake()->paragraph(),
        ];
    }
}
