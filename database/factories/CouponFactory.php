<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('??????').$this->faker->randomDigitNotNull()),
            'type' => $this->faker->randomElement(['percentage', 'fixed']),
            'value' => $this->faker->randomElement([10.00, 20.00, 50.00]),
            'min_order_amount' => null,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'description' => $this->faker->sentence(),
        ];
    }
}
