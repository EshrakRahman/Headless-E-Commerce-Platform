<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id', 'name');

        $products = [
            // Clothing (25)
            ['Clothing', 'Classic Fit Oxford Shirt', 49.99, 69.99],
            ['Clothing', 'Slim Fit Chino Pants', 59.99, null],
            ['Clothing', 'Premium Cotton T-Shirt', 29.99, 39.99],
            ['Clothing', 'Lightweight Denim Jacket', 89.99, 129.99],
            ['Clothing', 'Casual Linen Blazer', 119.99, null],
            ['Clothing', 'Athletic Performance Shorts', 34.99, 44.99],
            ['Clothing', 'Merino Wool Sweater', 79.99, null],
            ['Clothing', 'Stretch Fit Skinny Jeans', 69.99, 89.99],
            ['Clothing', 'Waterproof Rain Jacket', 129.99, 169.99],
            ['Clothing', 'Cotton Hoodie', 54.99, null],
            ['Clothing', 'Pleated Trousers', 64.99, 79.99],
            ['Clothing', 'Polo Shirt', 44.99, null],
            ['Clothing', 'Quilted Vest', 74.99, 99.99],
            ['Clothing', 'Cargo Shorts', 39.99, null],
            ['Clothing', 'Formal Dress Shirt', 59.99, 79.99],
            ['Clothing', 'Leather Bomber Jacket', 199.99, 259.99],
            ['Clothing', 'Graphic Print T-Shirt', 24.99, null],
            ['Clothing', 'High-Waist Trousers', 54.99, 69.99],
            ['Clothing', 'Cardigan Sweater', 69.99, null],
            ['Clothing', 'Track Pants', 44.99, 59.99],
            ['Clothing', 'Denim Shirt', 49.99, null],
            ['Clothing', 'Wool Overcoat', 249.99, 349.99],
            ['Clothing', 'Summer Dress', 39.99, 54.99],
            ['Clothing', 'Tailored Blazer', 159.99, null],
            ['Clothing', 'Casual Joggers', 49.99, 64.99],

            // Shoes (15)
            ['Shoes', 'Running Pro Sneakers', 119.99, 149.99],
            ['Shoes', 'Leather Chelsea Boots', 169.99, null],
            ['Shoes', 'Classic Canvas Sneakers', 49.99, 64.99],
            ['Shoes', 'Formal Oxford Shoes', 139.99, 179.99],
            ['Shoes', 'Slip-On Loafers', 89.99, null],
            ['Shoes', 'Trail Hiking Boots', 149.99, 199.99],
            ['Shoes', 'Casual Espadrilles', 39.99, null],
            ['Shoes', 'Basketball High-Tops', 129.99, 159.99],
            ['Shoes', 'Leather Sandals', 59.99, 79.99],
            ['Shoes', 'Winter Snow Boots', 179.99, null],
            ['Shoes', 'Minimalist White Sneakers', 79.99, 99.99],
            ['Shoes', 'Wingtip Brogues', 159.99, null],
            ['Shoes', 'Flip Flops', 19.99, 29.99],
            ['Shoes', 'Ankle Boots', 119.99, 149.99],
            ['Shoes', 'Platform Sneakers', 89.99, null],

            // Accessories (15)
            ['Accessories', 'Minimalist Leather Watch', 199.99, 279.99],
            ['Accessories', 'Canvas Backpack', 69.99, 89.99],
            ['Accessories', 'Premium Leather Belt', 49.99, null],
            ['Accessories', 'Aviator Sunglasses', 129.99, 179.99],
            ['Accessories', 'Wool Beanie', 24.99, null],
            ['Accessories', 'Leather Messenger Bag', 149.99, 199.99],
            ['Accessories', 'Sports Cap', 19.99, 29.99],
            ['Accessories', 'Silver Chain Necklace', 89.99, null],
            ['Accessories', 'Leather Wallet', 44.99, 59.99],
            ['Accessories', 'Silk Scarf', 39.99, null],
            ['Accessories', 'Digital Watch', 149.99, 199.99],
            ['Accessories', 'Tote Bag', 79.99, null],
            ['Accessories', 'Leather Gloves', 54.99, 69.99],
            ['Accessories', 'Sun Hat', 29.99, null],
            ['Accessories', 'Luggage Tag Set', 14.99, 24.99],

            // Electronics (25)
            ['Electronics', 'Wireless Noise-Cancelling Headphones', 249.99, 349.99],
            ['Electronics', 'Bluetooth Speaker', 79.99, 99.99],
            ['Electronics', 'Fast Wireless Charger', 34.99, null],
            ['Electronics', 'Phone Case', 24.99, 39.99],
            ['Electronics', 'USB-C Hub Adapter', 44.99, null],
            ['Electronics', 'Portable Power Bank', 59.99, 79.99],
            ['Electronics', 'Mechanical Keyboard', 129.99, 169.99],
            ['Electronics', 'Wireless Mouse', 49.99, null],
            ['Electronics', 'Webcam 4K', 89.99, 119.99],
            ['Electronics', 'Laptop Stand', 39.99, null],
            ['Electronics', 'Smart LED Light Strip', 29.99, 44.99],
            ['Electronics', 'Noise-Cancelling Earbuds', 179.99, 229.99],
            ['Electronics', 'Dash Cam', 99.99, null],
            ['Electronics', 'Fitness Tracker', 69.99, 89.99],
            ['Electronics', 'Smart Plug', 19.99, null],
            ['Electronics', 'Cable Management Kit', 14.99, 24.99],
            ['Electronics', 'Gaming Headset', 89.99, 129.99],
            ['Electronics', 'Monitor Arm Mount', 59.99, null],
            ['Electronics', 'Desk LED Lamp', 44.99, 59.99],
            ['Electronics', 'Screen Protector 3-Pack', 12.99, null],
            ['Electronics', 'Car Phone Mount', 19.99, 29.99],
            ['Electronics', 'Wireless Presenter', 34.99, null],
            ['Electronics', 'HDMI Cable 6ft', 9.99, 14.99],
            ['Electronics', 'Smart Thermostat', 129.99, 169.99],
            ['Electronics', 'Electric Toothbrush', 79.99, null],

            // Home & Kitchen (20)
            ['Home & Kitchen', 'Insulated Water Bottle', 29.99, 39.99],
            ['Home & Kitchen', 'Bamboo Cutting Board', 24.99, null],
            ['Home & Kitchen', 'Stainless Steel French Press', 44.99, 59.99],
            ['Home & Kitchen', 'Scented Candle Set', 34.99, null],
            ['Home & Kitchen', 'Kitchen Knife Set', 89.99, 129.99],
            ['Home & Kitchen', 'Plant Pot with Stand', 39.99, null],
            ['Home & Kitchen', 'Glass Food Containers', 29.99, 44.99],
            ['Home & Kitchen', 'Memory Foam Pillow', 49.99, 69.99],
            ['Home & Kitchen', 'Microfiber Cleaning Set', 19.99, null],
            ['Home & Kitchen', 'Cast Iron Skillet', 54.99, 74.99],
            ['Home & Kitchen', 'LED Desk Clock', 24.99, null],
            ['Home & Kitchen', 'Wall Mounted Shelf', 34.99, 49.99],
            ['Home & Kitchen', 'Reusable Silicone Bags', 14.99, null],
            ['Home & Kitchen', 'Ceramic Mug Set', 29.99, 44.99],
            ['Home & Kitchen', 'Shoe Rack', 39.99, null],
            ['Home & Kitchen', 'Sushi Making Kit', 24.99, 39.99],
            ['Home & Kitchen', 'Laundry Hamper', 34.99, null],
            ['Home & Kitchen', 'Over-the-Door Organizer', 19.99, 29.99],
            ['Home & Kitchen', 'Electric Kettle', 44.99, null],
            ['Home & Kitchen', 'Bath Towel Set', 39.99, 54.99],
        ];

        foreach ($products as $data) {
            $slug = Str::slug($data[1]);

            Product::create([
                'category_id' => $categoryIds[$data[0]],
                'name' => $data[1],
                'slug' => $slug,
                'description' => fake()->paragraphs(3, true),
                'price' => $data[2],
                'compare_price' => $data[3],
                'quantity' => fake()->numberBetween(10, 100),
                'image' => 'https://picsum.photos/seed/'.$slug.'/640/480',
                'is_featured' => fake()->boolean(20),
            ]);
        }

        $this->command->info('Created '.count($products).' products.');
    }
}
