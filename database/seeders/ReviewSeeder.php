<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $products = Product::all();

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        // We want to create some featured reviews to display on the home page.
        // Let's create about 6 featured reviews using existing products and users.
        $featuredReviewsData = [
            [
                'rating' => 5,
                'title' => 'Absolutely perfect!',
                'body' => 'This product exceeded all my expectations. The quality is exceptional and the design is beautiful.',
            ],
            [
                'rating' => 5,
                'title' => 'Highly recommended',
                'body' => 'I have been using this for a week now and it has completely changed my daily routine. Must buy!',
            ],
            [
                'rating' => 5,
                'title' => 'Incredible value',
                'body' => 'Excellent build quality and very fast shipping. Will definitely purchase again.',
            ],
            [
                'rating' => 4,
                'title' => 'Very satisfied',
                'body' => 'Great product with solid performance. Minor issue with packaging but the item itself is perfect.',
            ],
            [
                'rating' => 5,
                'title' => 'Best purchase ever',
                'body' => 'Stunning quality, looks premium and works like a charm. Worth every single penny.',
            ],
            [
                'rating' => 5,
                'title' => 'Outstanding quality',
                'body' => 'I am super impressed with the customer service and product durability. Highly recommended.',
            ],
        ];

        // Seed featured reviews for a subset of products
        $shuffledProducts = $products->shuffle();
        foreach ($featuredReviewsData as $index => $reviewData) {
            $product = $shuffledProducts->get($index % $products->count());
            $user = $users->random();

            Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ],
                [
                    'rating' => $reviewData['rating'],
                    'title' => $reviewData['title'],
                    'body' => $reviewData['body'],
                    'is_approved' => true,
                    'is_featured' => true,
                ]
            );
        }

        // Seed some regular (non-featured, but approved) reviews for general variety
        foreach ($products->take(10) as $product) {
            $availableUsers = $users->filter(fn ($u) => ! Review::where('user_id', $u->id)->where('product_id', $product->id)->exists());
            if ($availableUsers->isEmpty()) {
                continue;
            }

            $count = fake()->numberBetween(1, 2);
            for ($i = 0; $i < $count; $i++) {
                if ($availableUsers->isEmpty()) {
                    break;
                }
                $user = $availableUsers->random();
                // Remove from collection to prevent duplicate review in the loop
                $availableUsers = $availableUsers->reject(fn ($u) => $u->id === $user->id);

                Review::factory()
                    ->approved()
                    ->create([
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'is_featured' => false,
                    ]);
            }
        }
    }
}
