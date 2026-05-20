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
