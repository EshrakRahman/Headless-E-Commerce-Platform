<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

// ─── General API Rate Limiting ──────────────────────────────────────

it('returns rate limit headers on API endpoints', function () {
    $response = $this->getJson('/api/v1/products');

    $response->assertSuccessful()
        ->assertHeader('X-RateLimit-Limit')
        ->assertHeader('X-RateLimit-Remaining');
});

it('returns 429 when general API rate limit is exceeded', function () {
    RateLimiter::clear('api:127.0.0.1');

    for ($i = 0; $i < 120; $i++) {
        $this->getJson('/api/v1/products');
    }

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(429)
        ->assertJson(['message' => 'Too Many Attempts.']);
});

it('resets rate limit headers per user basis', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Product::factory()->create(['quantity' => 10, 'price' => 10]);

    RateLimiter::clear('api:'.$user1->id);
    RateLimiter::clear('api:'.$user2->id);

    // User 1 makes many requests
    for ($i = 0; $i < 120; $i++) {
        $this->actingAs($user1, 'sanctum')->getJson('/api/v1/products');
    }

    // User 2 should still be able to make requests
    $response = $this->actingAs($user2, 'sanctum')
        ->getJson('/api/v1/products');

    $response->assertSuccessful();
});

// ─── Auth Rate Limiting ─────────────────────────────────────────────

it('returns 429 when login rate limit is exceeded', function () {
    RateLimiter::clear('auth:127.0.0.1');

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
});

it('returns 429 when register rate limit is exceeded', function () {
    RateLimiter::clear('auth:127.0.0.1');

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => "test{$i}@example.com",
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }

    $response = $this->postJson('/api/register', [
        'name' => 'Test',
        'email' => 'test99@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(429);
});

// ─── Orders Rate Limiting ───────────────────────────────────────────

it('returns 429 when orders rate limit is exceeded', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 10, 'quantity' => 100]);

    RateLimiter::clear('orders:'.$user->id);

    $payload = [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
        'shipping_address' => [
            'name' => 'John', 'phone' => '123', 'address' => 'a',
            'city' => 'NY', 'state' => 'NY', 'zip' => '10001', 'country' => 'US',
        ],
    ];

    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders', $payload);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', $payload);

    $response->assertStatus(429);
});

// ─── Webhook Endpoint (No Throttle) ─────────────────────────────────

it('does not rate limit the webhook endpoint', function () {
    RateLimiter::clear('api:127.0.0.1');

    for ($i = 0; $i < 150; $i++) {
        $this->postJson('/webhooks/stripe', [
            'type' => 'payment_intent.succeeded',
        ], ['Stripe-Signature' => 'fake_sig']);
    }

    $response = $this->postJson('/webhooks/stripe', [
        'type' => 'payment_intent.succeeded',
    ], ['Stripe-Signature' => 'fake_sig']);

    // Should not return 429 — returns 400 due to invalid signature, proving no throttle
    $response->assertStatus(400);
});
