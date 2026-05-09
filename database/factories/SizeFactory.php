<?php

namespace Database\Factories;

use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Size>
 */
class SizeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
        ];
    }
}
