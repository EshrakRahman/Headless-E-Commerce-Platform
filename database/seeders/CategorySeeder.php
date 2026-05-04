<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Clothing', 'description' => 'T-shirts, shirts, jeans, jackets and more'],
            ['name' => 'Shoes', 'description' => 'Sneakers, boots, sandals and formal shoes'],
            ['name' => 'Accessories', 'description' => 'Watches, bags, belts, hats and sunglasses'],
            ['name' => 'Electronics', 'description' => 'Headphones, speakers, chargers and gadgets'],
            ['name' => 'Home & Kitchen', 'description' => 'Bottles, organizers, tools and decor'],
        ];

        foreach ($categories as $data) {
            Category::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Created '.count($categories).' categories.');
    }
}
