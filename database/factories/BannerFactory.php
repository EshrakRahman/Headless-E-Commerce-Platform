<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    public function definition(): array
    {
        $bgColors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#fa709a', '#30cfd0', '#a8edea', '#fbc2eb'];

        return [
            'title' => ucwords(implode(' ', [fake()->randomElement(['Summer', 'Winter', 'Spring', 'Autumn', 'New', 'Limited', 'Exclusive']), fake()->randomElement(['Collection', 'Sale', 'Arrivals', 'Deals', 'Essentials'])]).' '.date('Y')),
            'subtitle' => 'Up to '.fake()->numberBetween(20, 70).'% off on selected items',
            'cta_text' => fake()->randomElement(['Shop Now', 'Explore', 'View Collection', 'Get the Look', 'Discover']),
            'cta_url' => '/'.fake()->randomElement(['new-arrivals', 'sale', 'shop', 'collections/summer', 'products']),
            'desktop_image' => null,
            'mobile_image' => null,
            'bg_color' => fake()->randomElement($bgColors),
            'text_color' => fake()->randomElement(['light', 'dark']),
            'position' => 'hero',
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
