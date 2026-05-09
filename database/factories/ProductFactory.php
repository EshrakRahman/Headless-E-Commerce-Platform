<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'image' => null,
            'price' => fake()->randomFloat(2, 5, 200),
            'compare_price' => null,
            'quantity' => fake()->numberBetween(0, 100),
            'is_featured' => fake()->boolean(20),
        ];
    }
}
