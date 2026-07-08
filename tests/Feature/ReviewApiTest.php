<?php

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── List Reviews ───────────────────────────────────────────────────

it('lists approved reviews for a product', function () {
    $product = Product::factory()->create();
    Review::factory()->approved()->count(3)->create(['product_id' => $product->id]);
    Review::factory()->pending()->count(2)->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('includes average rating and total reviews in meta', function () {
    $product = Product::factory()->create();
    Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 5]);
    Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 3]);

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

    $response->assertSuccessful()
        ->assertJsonPath('meta.total_reviews', 2);

    $json = $response->json();
    expect($json['meta']['average_rating'])->toEqualWithDelta(4.0, 0.01);
});

it('returns empty data and null rating for products with no approved reviews', function () {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

    $response->assertSuccessful()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.average_rating', null)
        ->assertJsonPath('meta.total_reviews', 0);
});

it('paginates reviews', function () {
    $product = Product::factory()->create();
    Review::factory()->approved()->count(15)->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews?page=1");

    $response->assertSuccessful()
        ->assertJsonCount(10, 'data');
});

// ─── Create Review ──────────────────────────────────────────────────

it('creates a review for authenticated user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/reviews", [
            'rating' => 5,
            'title' => 'Great product!',
            'body' => 'This is an amazing product, highly recommend it to everyone.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.title', 'Great product!')
        ->assertJsonPath('data.is_approved', false);

    expect(Review::count())->toBe(1);
});

it('updates existing review on resubmit', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    Review::factory()->pending()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'rating' => 3,
        'body' => 'First review text here okay.',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/reviews", [
            'rating' => 5,
            'body' => 'Updated review text goes here now.',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.body', 'Updated review text goes here now.');

    expect(Review::count())->toBe(1);
});

it('requires authentication to create review', function () {
    $product = Product::factory()->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
        'rating' => 4,
        'body' => 'Good product, would recommend to others.',
    ]);

    $response->assertUnauthorized();
});

it('validates review input', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/reviews", [
            'rating' => 6,
            'body' => 'short',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rating', 'body']);
});

// ─── Delete Review ──────────────────────────────────────────────────

it('deletes own review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $review = Review::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/reviews/{$review->id}");

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Review deleted.');

    expect(Review::count())->toBe(0);
});

it('cannot delete another users review', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $product = Product::factory()->create();
    $review = Review::factory()->create([
        'user_id' => $user1->id,
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($user2, 'sanctum')
        ->deleteJson("/api/v1/reviews/{$review->id}");

    $response->assertForbidden();
    expect(Review::count())->toBe(1);
});

// ─── My Reviews ─────────────────────────────────────────────────────

it('lists the authenticated users reviews', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->create();

    Review::factory()->approved()->create(['user_id' => $user->id, 'product_id' => $product->id]);
    Review::factory()->pending()->create(['user_id' => $otherUser->id, 'product_id' => $product->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/my/reviews');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('requires authentication for my reviews', function () {
    $response = $this->getJson('/api/v1/my/reviews');

    $response->assertUnauthorized();
});

// ─── Featured Reviews ───────────────────────────────────────────────

it('lists approved featured reviews', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $user = User::factory()->create();

    // Approved + Featured
    Review::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product1->id,
        'is_approved' => true,
        'is_featured' => true,
        'title' => 'Featured Review 1',
    ]);

    // Approved but NOT Featured
    Review::factory()->create([
        'user_id' => User::factory()->create()->id,
        'product_id' => $product2->id,
        'is_approved' => true,
        'is_featured' => false,
        'title' => 'Regular Approved Review',
    ]);

    // Featured but NOT Approved
    Review::factory()->create([
        'user_id' => User::factory()->create()->id,
        'product_id' => $product1->id,
        'is_approved' => false,
        'is_featured' => true,
        'title' => 'Pending Featured Review',
    ]);

    $response = $this->getJson('/api/v1/reviews/featured');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Featured Review 1')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_name',
                    'product_id',
                    'product' => [
                        'id',
                        'name',
                        'slug',
                        'image',
                    ],
                    'rating',
                    'title',
                    'body',
                    'is_approved',
                    'is_featured',
                    'created_at',
                ],
            ],
        ]);
});

it('limits the number of featured reviews returned', function () {
    $product = Product::factory()->create();

    // Create 10 approved, featured reviews
    for ($i = 0; $i < 10; $i++) {
        Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $product->id,
            'is_approved' => true,
            'is_featured' => true,
        ]);
    }

    $response = $this->getJson('/api/v1/reviews/featured?limit=3');
    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');

    $responseDefault = $this->getJson('/api/v1/reviews/featured');
    $responseDefault->assertSuccessful()
        ->assertJsonCount(6, 'data'); // default limit is 6
});
