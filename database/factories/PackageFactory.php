<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Models\Business;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'business_id' => Business::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'category' => fake()->randomElement(array_column(config('booktrips.categories'), 'slug')),
            'description' => fake()->paragraph(),
            'highlight' => fake()->sentence(),
            'location' => fake()->city(),
            'address' => fake()->streetAddress(),
            'district' => fake()->city(),
            'lat' => fake()->latitude(5.9, 9.8),
            'lng' => fake()->longitude(79.6, 81.9),
            'schedule_type' => 'always',
            'schedule_start' => null,
            'schedule_end' => null,
            'weekdays' => [0, 1, 2, 3, 4, 5, 6],
            'duration_days' => 1,
            'duration_nights' => 0,
            'price_lkr' => fake()->numberBetween(3000, 60000),
            'price_type' => PriceType::PerPackage,
            'discount_type' => DiscountType::None,
            'discount_value' => 0,
            'discount_enabled' => false,
            'discount_start' => null,
            'discount_end' => null,
            'min_guests' => 1,
            'max_guests' => 8,
            'included' => ['Guide', 'Drinking water'],
            'excluded' => ['Transport'],
            'itinerary' => [],
            'amenities' => [],
            'images' => [],
            'meeting_point' => null,
            'cancellation_policy' => 'Free cancellation up to 48 hours before the start time.',
            'rating' => 0,
            'review_count' => 0,
            'featured' => false,
            'active' => true,
        ];
    }

    /**
     * A package priced per person.
     */
    public function perPerson(): static
    {
        return $this->state(fn (array $attributes) => [
            'price_type' => PriceType::PerPerson,
        ]);
    }

    /**
     * A hidden package.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * A featured package.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    /**
     * A package with an active percentage discount.
     */
    public function discounted(int $percentage = 15): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => DiscountType::Percentage,
            'discount_value' => $percentage,
            'discount_enabled' => true,
        ]);
    }
}
