<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Seeder;

class ProductSizeSeeder extends Seeder
{
    public function run(): void
    {
        $categoryNames = ['Clothing', 'Shoes', 'Accessories'];
        $sizeIds = Size::pluck('id', 'name');

        $productIds = Product::whereHas('category', function ($q) use ($categoryNames) {
            $q->whereIn('name', $categoryNames);
        })->pluck('id');

        foreach ($productIds as $productId) {
            $selectedSizes = collect($sizeIds)->random(rand(2, 4));

            $pivotData = [];
            foreach ($selectedSizes as $sizeName => $sizeId) {
                $additionalPrice = match ($sizeName) {
                    'X-Large' => rand(2, 4),
                    'Large' => rand(0, 2),
                    default => 0,
                };

                $pivotData[$sizeId] = [
                    'additional_price' => $additionalPrice,
                    'stock' => rand(5, 30),
                ];
            }

            Product::find($productId)->sizes()->sync($pivotData);
        }

        $this->command->info('Assigned sizes to '.$productIds->count().' products.');
    }
}
