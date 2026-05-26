<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Size;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Signature('app:seed-stress-data {--products=100} {--orders=10} {--users=5}')]
#[Description('Seed the database with a large amount of stress test data very quickly')]
class SeedStressData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $totalProducts = (int) $this->option('products');
        $totalOrders = (int) $this->option('orders');
        $totalUsers = (int) $this->option('users');

        $this->info("Starting stress seeder with: {$totalProducts} products, {$totalOrders} orders, {$totalUsers} users.");

        $startTime = microtime(true);

        DB::beginTransaction();

        try {
            // 1. Ensure categories exist
            $categories = Category::all();
            if ($categories->isEmpty()) {
                $this->info('Seeding base categories...');
                $this->call('db:seed', ['--class' => 'CategorySeeder']);
                $categories = Category::all();
            }
            $categoryMap = $categories->pluck('id', 'name')->toArray();

            // Ensure brands exist
            $brands = Brand::all();
            if ($brands->isEmpty()) {
                $this->info('Seeding base brands...');
                $this->call('db:seed', ['--class' => 'BrandSeeder']);
                $brands = Brand::all();
            }
            $brandMapByName = $brands->pluck('id', 'name')->toArray();
            $brandIds = array_keys($brandMapByName);

            // 2. Ensure sizes exist
            $sizes = Size::all();
            if ($sizes->isEmpty()) {
                $this->info('Seeding base sizes...');
                $this->call('db:seed', ['--class' => 'SizeSeeder']);
                $sizes = Size::all();
            }
            $sizeIds = $sizes->pluck('id')->toArray();

            // 3. Seed Users
            $this->info('Seeding users...');
            $startUserId = (DB::table('users')->max('id') ?? 0) + 1;
            $passwordHash = Hash::make('password');
            $now = now();

            $userChunks = [];
            for ($i = 0; $i < $totalUsers; $i++) {
                $userId = $startUserId + $i;
                $userChunks[] = [
                    'id' => $userId,
                    'name' => "Stress User {$userId}",
                    'email' => "stress_user_{$userId}@example.com",
                    'email_verified_at' => $now,
                    'password' => $passwordHash,
                    'remember_token' => Str::random(10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($userChunks) >= 1000) {
                    DB::table('users')->insert($userChunks);
                    $userChunks = [];
                }
            }
            if (! empty($userChunks)) {
                DB::table('users')->insert($userChunks);
            }
            $this->info("Seeded {$totalUsers} users.");

            // 4. Seed Products
            $this->info('Seeding products...');
            
            $productPool = [
                // Shoes
                ['category' => 'Shoes', 'brand' => 'Nike', 'name' => 'Nike Air Max 270 Sneakers', 'price' => 150.00, 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&auto=format&fit=crop&q=80', 'description' => 'Features Nike\'s tallest Air unit yet, delivering a super-soft ride.'],
                ['category' => 'Shoes', 'brand' => 'Nike', 'name' => 'Nike Air Force 1 \'07', 'price' => 115.00, 'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&auto=format&fit=crop&q=80', 'description' => 'The radiance lives on in the basketball original.'],
                ['category' => 'Shoes', 'brand' => 'Adidas', 'name' => 'Adidas Ultraboost Light', 'price' => 190.00, 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&auto=format&fit=crop&q=80', 'description' => 'Experience epic energy with the lightest Ultraboost ever.'],
                ['category' => 'Shoes', 'brand' => 'Adidas', 'name' => 'Adidas Samba OG Shoes', 'price' => 100.00, 'image' => 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=600&auto=format&fit=crop&q=80', 'description' => 'Born on the pitch, the Samba is a timeless icon of street style.'],
                ['category' => 'Shoes', 'brand' => 'Zara', 'name' => 'Zara Minimalist Leather Sneakers', 'price' => 59.90, 'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&auto=format&fit=crop&q=80', 'description' => 'White leather sneakers with dynamic monochrome stitching.'],
                ['category' => 'Shoes', 'brand' => 'Zara', 'name' => 'Zara Leather Chelsea Boots', 'price' => 89.90, 'image' => 'https://images.unsplash.com/photo-1638247025967-b4e38f6893b8?w=600&auto=format&fit=crop&q=80', 'description' => 'Classic elastic side panel leather boots for everyday wear.'],
                
                // Clothing
                ['category' => 'Clothing', 'brand' => 'Nike', 'name' => 'Nike Club Fleece Hoodie', 'price' => 60.00, 'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600&auto=format&fit=crop&q=80', 'description' => 'Brushed-back fleece hoodie offering standard soft comfort.'],
                ['category' => 'Clothing', 'brand' => 'Nike', 'name' => 'Nike Dri-FIT Legend Tee', 'price' => 30.00, 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80', 'description' => 'Athletic t-shirt built with sweat-wicking materials.'],
                ['category' => 'Clothing', 'brand' => 'Adidas', 'name' => 'Adidas Essentials Track Pants', 'price' => 50.00, 'image' => 'https://images.unsplash.com/photo-1479064555552-3ef4979f8908?w=600&auto=format&fit=crop&q=80', 'description' => 'Slim fit track pants featuring the iconic three stripes.'],
                ['category' => 'Clothing', 'brand' => 'Levi\'s', 'name' => 'Levi\'s 501 Original Jeans', 'price' => 89.50, 'image' => 'https://images.unsplash.com/photo-1479064555552-3ef4979f8908?w=600&auto=format&fit=crop&q=80', 'description' => 'Straight fit button-fly original blue jeans.'],
                ['category' => 'Clothing', 'brand' => 'Levi\'s', 'name' => 'Levi\'s Denim Trucker Jacket', 'price' => 98.00, 'image' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&auto=format&fit=crop&q=80', 'description' => 'Original jean jacket silhouette since 1967.'],
                ['category' => 'Clothing', 'brand' => 'Zara', 'name' => 'Zara Oversized Wool Coat', 'price' => 169.00, 'image' => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&auto=format&fit=crop&q=80', 'description' => 'Premium wool blend double breasted long coat.'],
                ['category' => 'Clothing', 'brand' => 'Zara', 'name' => 'Zara Slim Fit Poplin Shirt', 'price' => 35.90, 'image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&auto=format&fit=crop&q=80', 'description' => 'Classic cotton poplin shirt with sharp spread collar.'],
                
                // Electronics
                ['category' => 'Electronics', 'brand' => 'Apple', 'name' => 'Apple iPhone 15 Pro', 'price' => 999.00, 'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80', 'description' => 'Forged in titanium, featuring the groundbreaking A17 Pro chip.'],
                ['category' => 'Electronics', 'brand' => 'Apple', 'name' => 'Apple MacBook Air M3', 'price' => 1099.00, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', 'description' => 'Superlight and fast laptop powered by next-generation M3 silicon.'],
                ['category' => 'Electronics', 'brand' => 'Apple', 'name' => 'Apple AirPods Pro 2', 'price' => 249.00, 'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80', 'description' => 'Rebuilt from the sound up, featuring 2x better active noise cancellation.'],
                ['category' => 'Electronics', 'brand' => 'Sony', 'name' => 'Sony WH-1000XM5 Headphones', 'price' => 398.00, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', 'description' => 'Industry-leading noise cancellation with exceptional sound clarity.'],
                ['category' => 'Electronics', 'brand' => 'Sony', 'name' => 'Sony PlayStation 5 Slim Console', 'price' => 499.00, 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&auto=format&fit=crop&q=80', 'description' => 'Experience lightning-fast loading and deeper gaming immersion.'],
                ['category' => 'Electronics', 'brand' => 'Samsung', 'name' => 'Samsung Galaxy S24 Ultra', 'price' => 1299.00, 'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80', 'description' => 'Welcome to the era of mobile AI, built with a titanium frame.'],
                ['category' => 'Electronics', 'brand' => 'Samsung', 'name' => 'Samsung 55" Neo QLED 4K TV', 'price' => 899.00, 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&auto=format&fit=crop&q=80', 'description' => 'Ultra-fine light control with Quantum Mini LEDs.'],
                ['category' => 'Electronics', 'brand' => 'Dell', 'name' => 'Dell XPS 13 Laptop', 'price' => 999.00, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', 'description' => 'Precision crafted from CNC aluminum with beautiful InfinityEdge display.'],
                ['category' => 'Electronics', 'brand' => 'Dell', 'name' => 'Dell UltraSharp 27" 4K Monitor', 'price' => 479.00, 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&auto=format&fit=crop&q=80', 'description' => 'Exceptional color coverage and clear 4K resolution.'],

                // Accessories
                ['category' => 'Accessories', 'brand' => 'Apple', 'name' => 'Apple Watch Series 9 GPS', 'price' => 399.00, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80', 'description' => 'Smarter, brighter, and more powerful with the S9 SiP.'],
                ['category' => 'Accessories', 'brand' => 'Apple', 'name' => 'Apple AirTag 4-Pack', 'price' => 99.00, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80', 'description' => 'Keep track of and find your items alongside friends and devices.'],
                ['category' => 'Accessories', 'brand' => 'Nike', 'name' => 'Nike Classic Club Duffel Bag', 'price' => 45.00, 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&auto=format&fit=crop&q=80', 'description' => 'Spacious gym and travel duffel with water-resistant base.'],
                ['category' => 'Accessories', 'brand' => 'Nike', 'name' => 'Nike Polarized Sport Sunglasses', 'price' => 89.00, 'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=600&auto=format&fit=crop&q=80', 'description' => 'Sleek wraparound sunglasses designed to stay secure during motion.'],
                ['category' => 'Accessories', 'brand' => 'Zara', 'name' => 'Zara Structured Leather Belt', 'price' => 29.90, 'image' => 'https://images.unsplash.com/photo-1624222247344-550fb8ecf7db?w=600&auto=format&fit=crop&q=80', 'description' => 'Elegant classic black leather strap with brushed nickel buckle.'],
                ['category' => 'Accessories', 'brand' => 'Zara', 'name' => 'Zara Canvas Travel Backpack', 'price' => 49.90, 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&auto=format&fit=crop&q=80', 'description' => 'Casual styling with ample pockets and padded straps.'],
                ['category' => 'Accessories', 'brand' => 'Levi\'s', 'name' => 'Levi\'s Trifold Leather Wallet', 'price' => 38.00, 'image' => 'https://images.unsplash.com/photo-1627124224423-424a779ab6a7?w=600&auto=format&fit=crop&q=80', 'description' => 'Genuine hand-stitched leather wallet with secure RFID shielding.'],

                // Home & Kitchen
                ['category' => 'Home & Kitchen', 'brand' => 'Samsung', 'name' => 'Samsung Bespoke Air Purifier', 'price' => 349.00, 'image' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?w=600&auto=format&fit=crop&q=80', 'description' => 'Modern air purification with a 360-degree filtration system.'],
                ['category' => 'Home & Kitchen', 'brand' => 'Samsung', 'name' => 'Samsung Jet Bot Robot Vacuum', 'price' => 599.00, 'image' => 'https://images.unsplash.com/photo-1584269600464-37b1b58a9fe7?w=600&auto=format&fit=crop&q=80', 'description' => 'Intelligent LiDAR cleaning sensor maps your entire home.'],
            ];

            $startProductId = (DB::table('products')->max('id') ?? 0) + 1;
            if ($startProductId > 1) {
                // If table already has products, let's map the start ID to 1 to check/update existing first!
                $startProductId = 1;
            }

            $productChunks = [];
            $productSizeChunks = [];

            for ($i = 0; $i < $totalProducts; $i++) {
                $productId = $startProductId + $i;
                $base = $productPool[$i % count($productPool)];

                $categoryId = $categoryMap[$base['category']];
                $brandId = $brandMapByName[$base['brand']];

                $colors = ['Charcoal', 'Alabaster', 'Ocean Blue', 'Obsidian', 'Starlight', 'Titanium Gray', 'Crimson Red', 'Forest Green'];
                $color = $colors[$i % count($colors)];

                $name = $base['name'] . " - " . $color;
                $price = (float) $base['price'];
                $description = $base['description'] . " Color variant: " . $color . ".";
                $image = $base['image'];
                $comparePrice = rand(1, 10) <= 4 ? round($price * 1.25, 2) : null;
                $quantity = rand(10, 100);
                $isFeatured = rand(1, 10) <= 2;

                $existing = DB::table('products')->where('id', $productId)->first();

                if ($existing) {
                    if ($existing->updated_at !== $existing->created_at) {
                        // Product has been modified by the admin, do not touch!
                        continue;
                    }

                    DB::table('products')->where('id', $productId)->update([
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'name' => $name,
                        'description' => $description,
                        'image' => $image,
                        'price' => $price,
                        'compare_price' => $comparePrice,
                        'quantity' => $quantity,
                        'is_featured' => $isFeatured,
                        'updated_at' => $now,
                    ]);
                } else {
                    $productChunks[] = [
                        'id' => $productId,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'name' => $name,
                        'slug' => Str::slug($name).'-'.Str::random(5),
                        'description' => $description,
                        'image' => $image,
                        'price' => $price,
                        'compare_price' => $comparePrice,
                        'quantity' => $quantity,
                        'is_featured' => $isFeatured,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Associate sizes with 30% of the products (only if creating new)
                if (!$existing && rand(1, 10) <= 3) {
                    $selectedSizes = (array) array_rand(array_flip($sizeIds), rand(2, 4));
                    foreach ($selectedSizes as $sizeId) {
                        $productSizeChunks[] = [
                            'product_id' => $productId,
                            'size_id' => $sizeId,
                            'additional_price' => rand(0, 1) ? rand(200, 500) / 100 : 0,
                            'stock' => rand(5, 30),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            if (! empty($productChunks)) {
                DB::table('products')->insert($productChunks);
            }
            if (! empty($productSizeChunks)) {
                DB::table('product_size')->insert($productSizeChunks);
            }
            $this->info("Seeded {$totalProducts} products.");

            // 5. Fetch a sample of products for orders
            $sampleProducts = DB::table('products')
                ->where('id', '>=', $startProductId)
                ->limit(1000)
                ->get(['id', 'name', 'price'])
                ->toArray();

            if (empty($sampleProducts)) {
                $sampleProducts = DB::table('products')
                    ->limit(1000)
                    ->get(['id', 'name', 'price'])
                    ->toArray();
            }

            // 6. Seed Orders & Order Items
            $this->info('Seeding orders...');
            $startOrderId = (DB::table('orders')->max('id') ?? 0) + 1;
            $startOrderItemId = (DB::table('order_items')->max('id') ?? 0) + 1;

            $orderChunks = [];
            $orderItemChunks = [];
            $orderItemIndex = 0;

            for ($o = 0; $o < $totalOrders; $o++) {
                $orderId = $startOrderId + $o;
                $userId = $startUserId + rand(0, $totalUsers - 1);

                $itemCount = rand(1, 3);
                $subtotal = 0;

                for ($i = 0; $i < $itemCount; $i++) {
                    $product = $sampleProducts[array_rand($sampleProducts)];
                    $qty = rand(1, 3);
                    $price = (float) $product->price;
                    $itemSubtotal = $price * $qty;
                    $subtotal += $itemSubtotal;

                    $orderItemChunks[] = [
                        'id' => $startOrderItemId + $orderItemIndex++,
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'size_id' => null,
                        'product_name' => $product->name,
                        'size_name' => null,
                        'unit_price' => $price,
                        'quantity' => $qty,
                        'subtotal' => $itemSubtotal,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $shipping = 10.00;
                $discount = 0.00;
                $total = $subtotal + $shipping - $discount;

                $orderChunks[] = [
                    'id' => $orderId,
                    'user_id' => $userId,
                    'order_number' => 'ORD-'.$now->format('Ymd').'-'.str_pad($orderId, 6, '0', STR_PAD_LEFT),
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shipping,
                    'discount' => $discount,
                    'total' => $total,
                    'notes' => 'Stress test order.',
                    'payment_method' => 'stripe',
                    'payment_status' => 'pending',
                    'shipping_address' => json_encode([
                        'line1' => '123 Stress St',
                        'city' => 'Stressville',
                        'country' => 'US',
                        'postal_code' => '12345',
                    ]),
                    'billing_address' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($orderChunks) >= 1000) {
                    DB::table('orders')->insert($orderChunks);
                    $orderChunks = [];

                    DB::table('order_items')->insert($orderItemChunks);
                    $orderItemChunks = [];
                }
            }

            if (! empty($orderChunks)) {
                DB::table('orders')->insert($orderChunks);
            }
            if (! empty($orderItemChunks)) {
                DB::table('order_items')->insert($orderItemChunks);
            }
            $this->info("Seeded {$totalOrders} orders.");

            DB::commit();
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Database stress seeding completed successfully in {$duration}s!");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to seed database: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
