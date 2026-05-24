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
            // Clothing (8)
            [
                'Clothing',
                'Classic Fit Oxford Shirt',
                49.99,
                69.99,
                'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Clothing',
                'Slim Fit Chino Pants',
                59.99,
                null,
                'https://images.unsplash.com/photo-1479064555552-3ef4979f8908?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Clothing',
                'Premium Cotton T-Shirt',
                24.99,
                34.99,
                'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Clothing',
                'Lightweight Denim Jacket',
                89.99,
                119.99,
                'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Clothing',
                'Cotton Comfort Hoodie',
                54.99,
                null,
                'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Clothing',
                'Leather Bomber Jacket',
                199.99,
                259.99,
                'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Clothing',
                'Summer Linen Shorts',
                39.99,
                49.99,
                'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Clothing',
                'Structured Trench Coat',
                149.99,
                199.99,
                'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&auto=format&fit=crop&q=80'
            ],

            // Shoes (8)
            [
                'Shoes',
                'Running Pro Sneakers',
                119.99,
                149.99,
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Shoes',
                'Leather Chelsea Boots',
                169.99,
                null,
                'https://images.unsplash.com/photo-1638247025967-b4e38f6893b8?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Shoes',
                'Classic Canvas Sneakers',
                49.99,
                64.99,
                'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Shoes',
                'Formal Oxford Shoes',
                139.99,
                179.99,
                'https://images.unsplash.com/photo-1533867617858-e7b97e060509?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Shoes',
                'Slip-On Leather Loafers',
                89.99,
                null,
                'https://images.unsplash.com/photo-1614252369475-531eba835eb1?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Shoes',
                'Minimalist White Sneakers',
                79.99,
                99.99,
                'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Shoes',
                'Suede Desert Boots',
                129.99,
                159.99,
                'https://images.unsplash.com/photo-1520639888713-7851133b1ed0?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Shoes',
                'Outdoor Trail Hiking Shoes',
                159.99,
                199.99,
                'https://images.unsplash.com/photo-1582966772680-860e372bb558?w=600&auto=format&fit=crop&q=80'
            ],

            // Accessories (8)
            [
                'Accessories',
                'Minimalist Leather Watch',
                199.99,
                279.99,
                'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Accessories',
                'Canvas Travel Backpack',
                69.99,
                89.99,
                'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Accessories',
                'Aviator Sunglasses',
                129.99,
                179.99,
                'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Accessories',
                'Premium Leather Wallet',
                44.99,
                59.99,
                'https://images.unsplash.com/photo-1627124224423-424a779ab6a7?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Accessories',
                'Silver Chain Necklace',
                89.99,
                null,
                'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Accessories',
                'Classic Wool Beanie',
                24.99,
                null,
                'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Accessories',
                'Structured Leather Belt',
                34.99,
                49.99,
                'https://images.unsplash.com/photo-1624222247344-550fb8ecf7db?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Accessories',
                'Polarized Sports Sunglasses',
                109.99,
                139.99,
                'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=600&auto=format&fit=crop&q=80'
            ],

            // Electronics (8)
            [
                'Electronics',
                'Wireless Noise-Cancelling Headphones',
                249.99,
                349.99,
                'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Electronics',
                'Bluetooth Portable Speaker',
                79.99,
                99.99,
                'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Electronics',
                'Mechanical Gaming Keyboard',
                129.99,
                169.99,
                'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Electronics',
                'Wireless Ergonomic Mouse',
                49.99,
                null,
                'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Electronics',
                'Noise-Cancelling Earbuds',
                179.99,
                229.99,
                'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Electronics',
                'Webcam HD Pro',
                89.99,
                119.99,
                'https://images.unsplash.com/photo-1627914488344-90aaef1b70ca?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Electronics',
                'Dual USB-C Fast Charger',
                29.99,
                39.99,
                'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Electronics',
                'Minimalist Desk Mat',
                39.99,
                null,
                'https://images.unsplash.com/photo-1632292224971-0d45778bd364?w=600&auto=format&fit=crop&q=80'
            ],

            // Home & Kitchen (8)
            [
                'Home & Kitchen',
                'Insulated Water Bottle',
                29.99,
                39.99,
                'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Home & Kitchen',
                'Bamboo Cutting Board',
                24.99,
                null,
                'https://images.unsplash.com/photo-1584269600464-37b1b58a9fe7?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Home & Kitchen',
                'Stainless Steel French Press',
                44.99,
                59.99,
                'https://images.unsplash.com/photo-1577968897966-3d4325b36b61?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Home & Kitchen',
                'Scented Wax Candle Set',
                34.99,
                null,
                'https://images.unsplash.com/photo-1603006905003-be475563bc59?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Home & Kitchen',
                'Premium Chef Knife',
                89.99,
                129.99,
                'https://images.unsplash.com/photo-1593113630400-ea4288922497?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Home & Kitchen',
                'Ceramic Coffee Mug Set',
                29.99,
                44.99,
                'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Home & Kitchen',
                'Electric Gooseneck Kettle',
                119.99,
                149.99,
                'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&auto=format&fit=crop&q=80'
            ],
            [
                'Home & Kitchen',
                'Automatic Pepper Grinder Set',
                54.99,
                69.99,
                'https://images.unsplash.com/photo-1588854337236-6889d631faa8?w=600&auto=format&fit=crop&q=80'
            ],
        ];

        foreach ($products as $data) {
            $slug = Str::slug($data[1]);

            Product::create([
                'category_id' => $categoryIds[$data[0]],
                'name' => $data[1],
                'slug' => $slug,
                'description' => "High-quality {$data[1]} designed for comfort and style. Perfect for everyday wear with a modern fit and premium materials.",
                'price' => $data[2],
                'compare_price' => $data[3],
                'quantity' => rand(10, 100),
                'image' => $data[4],
                'is_featured' => rand(1, 100) <= 25,
            ]);
        }

        $this->command->info('Created '.count($products).' products.');
    }
}
