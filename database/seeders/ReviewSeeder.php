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

        // Seed some regular (non-featured, but approved) reviews for general variety using static content
        // to avoid dependency on Faker (which is dev-only and absent in production --no-dev environments).
        $regularReviewsData = [
            ['rating' => 4, 'title' => 'Pretty good', 'body' => 'Works well, but setup took a bit longer than expected.'],
            ['rating' => 5, 'title' => 'Love it!', 'body' => 'Exceeded my expectations. Great customer support too.'],
            ['rating' => 3, 'title' => 'Average', 'body' => 'Decent product, but there are better options out there for the price.'],
            ['rating' => 5, 'title' => 'Superb', 'body' => 'High quality material, very durable. Highly recommended.'],
            ['rating' => 4, 'title' => 'Satisfied customer', 'body' => 'Exactly as described. Shipping was very fast.'],
            ['rating' => 2, 'title' => 'Disappointed', 'body' => 'Did not work as advertised. Will be returning it.'],
            ['rating' => 5, 'title' => 'Perfect fit', 'body' => 'Sizing was exactly correct. Very comfortable.'],
            ['rating' => 4, 'title' => 'Solid product', 'body' => 'Good value for the price. I would buy it again.'],
        ];

        foreach ($products->take(10) as $prodIndex => $product) {
            $availableUsers = $users->filter(fn ($u) => ! Review::where('user_id', $u->id)->where('product_id', $product->id)->exists());
            if ($availableUsers->isEmpty()) {
                continue;
            }

            // Seed up to 2 regular reviews per product using the static regular reviews array
            for ($i = 0; $i < 2; $i++) {
                if ($availableUsers->isEmpty()) {
                    break;
                }
                $user = $availableUsers->random();
                $availableUsers = $availableUsers->reject(fn ($u) => $u->id === $user->id);

                $reviewItem = $regularReviewsData[($prodIndex + $i) % count($regularReviewsData)];

                Review::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'rating' => $reviewItem['rating'],
                        'title' => $reviewItem['title'],
                        'body' => $reviewItem['body'],
                        'is_approved' => true,
                        'is_featured' => false,
                    ]
                );
            }
        }
    }
}
