<?php

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock Stripe payment service to bypass network call in test checkouts
    config()->set('stripe.secret', 'sk_test_dummy');

    $this->paymentService = Mockery::mock(PaymentService::class)->makePartial();
    $this->paymentService->shouldReceive('createIntent')
        ->andReturn([
            'client_secret' => 'pi_test_12345_secret_test',
            'payment_intent_id' => 'pi_test_12345',
        ]);

    $this->app->instance(PaymentService::class, $this->paymentService);
});

// ─── Authentication ──────────────────────────────────────────────────

it('requires authentication to apply a coupon', function () {
    $response = $this->postJson('/api/v1/coupons/apply', [
        'code' => 'WELCOME10',
        'subtotal' => 100.00,
    ]);

    $response->assertUnauthorized();
});

// ─── Standard Coupon Constraints ─────────────────────────────────────

it('successfully applies a valid active coupon', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE15',
        'type' => DiscountType::Percentage,
        'value' => 15.00,
        'min_order_amount' => null,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'save15', // test case-insensitivity
            'subtotal' => 100.00,
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'code' => 'SAVE15',
            'type' => 'percentage',
            'value' => 15.00,
            'discount_amount' => 15.00,
        ]);
});

it('rejects an invalid or non-existent coupon', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'INVALID',
            'subtotal' => 100.00,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('rejects an expired coupon', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create([
        'code' => 'EXPIRED',
        'is_active' => true,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDays(1),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'EXPIRED',
            'subtotal' => 100.00,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('rejects a coupon if subtotal is below minimum order amount', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create([
        'code' => 'MIN50',
        'min_order_amount' => 50.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'MIN50',
            'subtotal' => 45.00,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

// ─── Global Usage Limits ─────────────────────────────────────────────

it('rejects a coupon if it has reached its global usage limit', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create([
        'code' => 'LIMITED',
        'usage_limit' => 2,
        'is_active' => true,
    ]);

    // Create 2 orders using the coupon
    Order::factory()->create(['coupon_code' => 'LIMITED', 'status' => OrderStatus::Pending->value]);
    Order::factory()->create(['coupon_code' => 'LIMITED', 'status' => OrderStatus::Delivered->value]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'LIMITED',
            'subtotal' => 100.00,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code'])
        ->assertJsonPath('errors.code.0', 'This coupon has reached its usage limit.');
});

it('allows applying coupon if some orders using it were cancelled', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create([
        'code' => 'LIMITED',
        'usage_limit' => 1,
        'is_active' => true,
    ]);

    // Create a cancelled order with the coupon
    Order::factory()->create(['coupon_code' => 'LIMITED', 'status' => OrderStatus::Cancelled->value]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'LIMITED',
            'subtotal' => 100.00,
        ]);

    $response->assertSuccessful();
});

// ─── User-Specific Usage Limits ──────────────────────────────────────

it('rejects a coupon if it has reached its usage limit per user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $coupon = Coupon::factory()->create([
        'code' => 'ONCEPERUSER',
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);

    // Other user has used it
    Order::factory()->create([
        'user_id' => $otherUser->id,
        'coupon_code' => 'ONCEPERUSER',
        'status' => OrderStatus::Pending->value,
    ]);

    // Authenticated user has also used it
    Order::factory()->create([
        'user_id' => $user->id,
        'coupon_code' => 'ONCEPERUSER',
        'status' => OrderStatus::Pending->value,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'ONCEPERUSER',
            'subtotal' => 100.00,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code'])
        ->assertJsonPath('errors.code.0', 'You have already used this coupon the maximum number of times.');
});

it('allows applying coupon if user-specific usage limit is not reached', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $coupon = Coupon::factory()->create([
        'code' => 'ONCEPERUSER',
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);

    // Other user has used it
    Order::factory()->create([
        'user_id' => $otherUser->id,
        'coupon_code' => 'ONCEPERUSER',
        'status' => OrderStatus::Pending->value,
    ]);

    // Authenticated user has NOT used it yet (or has only cancelled orders)
    Order::factory()->create([
        'user_id' => $user->id,
        'coupon_code' => 'ONCEPERUSER',
        'status' => OrderStatus::Cancelled->value,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/apply', [
            'code' => 'ONCEPERUSER',
            'subtotal' => 100.00,
        ]);

    $response->assertSuccessful();
});

// ─── Checkout Integration ───────────────────────────────────────────

it('allows placing order with valid coupon and applies discount', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100.00, 'quantity' => 10]);
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => DiscountType::Percentage,
        'value' => 10.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'coupon_code' => 'SAVE10',
            'shipping_address' => [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ]);

    $response->assertSuccessful();

    $json = $response->json();
    expect($json['data']['coupon_code'])->toBe('SAVE10');
    expect($json['data']['discount'])->toEqualWithDelta(10.00, 0.01);
    expect($json['data']['total'])->toEqualWithDelta(90.00, 0.01);
});

it('rejects checkout if global coupon usage limit is exceeded', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100.00, 'quantity' => 10]);
    $coupon = Coupon::factory()->create([
        'code' => 'LIMITED',
        'usage_limit' => 1,
        'is_active' => true,
    ]);

    // Use up the coupon once
    Order::factory()->create(['coupon_code' => 'LIMITED', 'status' => OrderStatus::Pending->value]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'coupon_code' => 'LIMITED',
            'shipping_address' => [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code'])
        ->assertJsonPath('errors.coupon_code.0', 'This coupon has reached its usage limit.');
});

it('rejects checkout if user coupon usage limit is exceeded', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100.00, 'quantity' => 10]);
    $coupon = Coupon::factory()->create([
        'code' => 'ONCEPERUSER',
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);

    // Use up the coupon for this user
    Order::factory()->create([
        'user_id' => $user->id,
        'coupon_code' => 'ONCEPERUSER',
        'status' => OrderStatus::Pending->value,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'coupon_code' => 'ONCEPERUSER',
            'shipping_address' => [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code'])
        ->assertJsonPath('errors.coupon_code.0', 'You have already used this coupon the maximum number of times.');
});
