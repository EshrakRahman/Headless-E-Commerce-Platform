<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Skipping discount seeding.');

            return;
        }

        $discounts = [
            [
                'name' => 'Summer Clearance',
                'type' => 'percentage',
                'value' => 30,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(25),
                'is_active' => true,
            ],
            [
                'name' => 'Bundle Deal',
                'type' => 'fixed',
                'value' => 10,
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Flash Sale',
                'type' => 'percentage',
                'value' => 50,
                'starts_at' => now()->addDays(30),
                'ends_at' => now()->addDays(35),
                'is_active' => true,
            ],
        ];

        foreach ($discounts as $data) {
            $discount = Discount::updateOrCreate(['name' => $data['name']], $data);

            $assigned = $products->random(min(15, $products->count()));
            $discount->products()->sync($assigned->pluck('id'));
        }

        $this->command->info('Created '.count($discounts).' discounts assigned to products.');
    }
}
