<?php

namespace Database\Factories;

use App\Enums\BusinessType;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->partner(),
            'name' => fake()->company(),
            'type' => fake()->randomElement(BusinessType::cases()),
            'description' => fake()->sentence(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'phone' => fake()->numerify('07########'),
            'email' => fake()->unique()->companyEmail(),
            'website' => null,
            'cover_image' => null,
            'instagram' => null,
            'facebook' => null,
            'tiktok' => null,
            'whatsapp' => null,
            'approved' => true,
        ];
    }

    /**
     * A partner that is still waiting for approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => false,
        ]);
    }
}
