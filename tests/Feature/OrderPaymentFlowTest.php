<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('stripe.secret', 'sk_test_dummy');

    $this->paymentService = Mockery::mock(PaymentService::class)->makePartial();
    $this->paymentService->shouldReceive('createIntent')
        ->andReturn([
            'client_secret' => 'pi_test_12345_secret_test',
            'payment_intent_id' => 'pi_test_12345',
        ]);

    $this->app->instance(PaymentService::class, $this->paymentService);
});

// ─── Order Placement Creates PaymentIntent ──────────────────────────

it('creates a payment intent when placing an order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 25.00, 'quantity' => 10]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'stripe',
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

    $response->assertSuccessful()
        ->assertJsonPath('data.payment_status', 'pending')
        ->assertJsonPath('data.payment_intent_id', 'pi_test_12345')
        ->assertJsonPath('data.payment_intent_client_secret', 'pi_test_12345_secret_test')
        ->assertJsonPath('data.status', 'pending');

    $json = $response->json();
    expect($json['data']['total'])->toEqualWithDelta(50.0, 0.01);
});

it('decrements stock when order is placed', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00, 'quantity' => 5]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
            'shipping_address' => [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    expect($product->fresh()->quantity)->toBe(2);
});

it('creates order with sized product and sets payment intent', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 30.00, 'quantity' => null]);
    $size = Size::factory()->create();
    ProductSize::create([
        'product_id' => $product->id,
        'size_id' => $size->id,
        'additional_price' => 10.00,
        'stock' => 5,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'quantity' => 2,
                ],
            ],
            'shipping_address' => [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $response->assertJsonPath('data.payment_intent_id', 'pi_test_12345')
        ->assertJsonPath('data.payment_intent_client_secret', 'pi_test_12345_secret_test');

    $json = $response->json();
    expect($json['data']['total'])->toEqualWithDelta(80.0, 0.01); // (30 + 10) * 2

    expect(ProductSize::where('product_id', $product->id)
        ->where('size_id', $size->id)
        ->first()
        ->stock
    )->toBe(3);
});

it('requires authentication to place an order', function () {
    $product = Product::factory()->create(['price' => 25.00, 'quantity' => 10]);

    $response = $this->postJson('/api/v1/orders', [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
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

    $response->assertUnauthorized();
});

// ─── Stock Validation ──────────────────────────────────────────────

it('rejects order with insufficient stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00, 'quantity' => 2]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
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
        ->assertJsonValidationErrors(['items.*.quantity']);
});

it('rejects order with invalid size id', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00, 'quantity' => 5]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'size_id' => 9999,
                    'quantity' => 1,
                ],
            ],
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
        ->assertJsonValidationErrors(['items.0.size_id']);
});

// ─── Order Listing ──────────────────────────────────────────────────

it('lists only the authenticated users orders', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $product = Product::factory()->create(['price' => 10.00, 'quantity' => 20]);

    $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'User 1',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $this->actingAs($user2, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'User 2',
                'phone' => '1234567890',
                'address' => '456 Main St',
                'city' => 'LA',
                'state' => 'CA',
                'zip' => '90001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/orders');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

// ─── Order Detail ───────────────────────────────────────────────────

it('shows order details for the owner', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00, 'quantity' => 10]);

    $orderResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'shipping_address' => [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/orders/{$orderId}");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $orderId)
        ->assertJsonPath('data.payment_status', 'pending')
        ->assertJsonPath('data.payment_intent_id', 'pi_test_12345');
});

it('rejects showing order that belongs to another user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00, 'quantity' => 10]);

    $orderResponse = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'User 1',
                'phone' => '1234567890',
                'address' => '123 Main St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $this->actingAs($user2, 'sanctum')
        ->getJson("/api/v1/orders/{$orderId}")
        ->assertForbidden();
});

// ─── Retry Payment ──────────────────────────────────────────────────

it('retries payment for a failed order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 20.00, 'quantity' => 10]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'John',
                'phone' => '1234567890',
                'address' => '123 St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $response->json('data.id');

    // Setup mock for retry: cancelIntent should be called, then createIntent for new intent
    $retryService = Mockery::mock(PaymentService::class)->makePartial();
    $retryService->shouldReceive('cancelIntent')->once();
    $retryService->shouldReceive('createIntent')->once()->andReturn([
        'client_secret' => 'pi_new_secret_test',
        'payment_intent_id' => 'pi_new_67890',
    ]);
    $this->app->instance(PaymentService::class, $retryService);

    // Set order as failed to simulate a failed payment
    Order::find($orderId)->update(['payment_status' => PaymentStatus::Failed->value]);

    $retryResponse = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/orders/{$orderId}/retry-payment");

    $retryResponse->assertSuccessful()
        ->assertJsonPath('payment_intent_id', 'pi_new_67890')
        ->assertJsonPath('payment_intent_client_secret', 'pi_new_secret_test');

    expect(Order::find($orderId)->payment_status)->toBe(PaymentStatus::Pending);
});

it('rejects retry payment on already paid order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 20.00, 'quantity' => 10]);

    $orderResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'John',
                'phone' => '1234567890',
                'address' => '123 St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    Order::find($orderId)->update(['payment_status' => PaymentStatus::Paid->value]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/orders/{$orderId}/retry-payment")
        ->assertStatus(422);
});

it('rejects retry payment from non-owner', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $product = Product::factory()->create(['price' => 20.00, 'quantity' => 10]);

    $orderResponse = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'User 1',
                'phone' => '1234567890',
                'address' => '123 St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $this->actingAs($user2, 'sanctum')
        ->postJson("/api/v1/orders/{$orderId}/retry-payment")
        ->assertForbidden();
});

// ─── Payment Status ─────────────────────────────────────────────────

it('returns payment status for an order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 15.00, 'quantity' => 5]);

    $orderResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'John',
                'phone' => '1234567890',
                'address' => '123 St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/orders/{$orderId}/payment-status");

    $response->assertSuccessful()
        ->assertJsonPath('order_id', $orderId)
        ->assertJsonPath('payment_status', 'pending')
        ->assertJsonPath('status', 'pending');
});

it('rejects payment status check for non-owner', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $product = Product::factory()->create(['price' => 15.00, 'quantity' => 5]);

    $orderResponse = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'User 1',
                'phone' => '1234567890',
                'address' => '123 St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $this->actingAs($user2, 'sanctum')
        ->getJson("/api/v1/orders/{$orderId}/payment-status")
        ->assertForbidden();
});

// ─── Confirm Payment ───────────────────────────────────────────────

it('confirms a successful payment via Stripe', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 25.00, 'quantity' => 10]);

    $orderResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'shipping_address' => [
                'name' => 'John',
                'phone' => '1234567890',
                'address' => '123 St',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $confirmService = Mockery::mock(PaymentService::class)->makePartial();
    $confirmService->shouldReceive('confirmPayment')->once()->andReturnUsing(function ($order) {
        $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);

        return ['paid' => true, 'status' => 'confirmed', 'payment_intent_status' => 'succeeded'];
    });
    $this->app->instance(PaymentService::class, $confirmService);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/orders/{$orderId}/confirm-payment");

    $response->assertSuccessful()
        ->assertJsonPath('order_id', $orderId)
        ->assertJsonPath('paid', true)
        ->assertJsonPath('payment_status', 'paid')
        ->assertJsonPath('status', 'confirmed')
        ->assertJsonPath('stripe_status', 'succeeded');
});

it('confirms a failed payment returns paid false', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 25.00, 'quantity' => 10]);

    $orderResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'John',
                'phone' => '123',
                'address' => 'a',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $confirmService = Mockery::mock(PaymentService::class)->makePartial();
    $confirmService->shouldReceive('confirmPayment')->once()->andReturn([
        'paid' => false,
        'status' => 'pending',
        'payment_intent_status' => 'requires_payment_method',
    ]);
    $this->app->instance(PaymentService::class, $confirmService);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/orders/{$orderId}/confirm-payment");

    $response->assertSuccessful()
        ->assertJsonPath('paid', false)
        ->assertJsonPath('stripe_status', 'requires_payment_method');
});

it('confirm-payment requires authentication', function () {
    $response = $this->postJson('/api/v1/orders/1/confirm-payment');

    $response->assertUnauthorized();
});

it('confirm-payment rejects non-owner', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00, 'quantity' => 5]);

    $orderResponse = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'User 1',
                'phone' => '123',
                'address' => 'a',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $this->actingAs($user2, 'sanctum')
        ->postJson("/api/v1/orders/{$orderId}/confirm-payment")
        ->assertForbidden();
});

it('returns order already paid when confirming a paid order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 25.00, 'quantity' => 10]);

    $orderResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'name' => 'John',
                'phone' => '123',
                'address' => 'a',
                'city' => 'NY',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US',
            ],
        ])
        ->assertSuccessful();

    $orderId = $orderResponse->json('data.id');

    $confirmService = Mockery::mock(PaymentService::class)->makePartial();
    $confirmService->shouldReceive('confirmPayment')->once()->andReturnUsing(function ($order) {
        $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);

        return ['paid' => true, 'status' => 'confirmed', 'payment_intent_status' => 'succeeded'];
    });
    $this->app->instance(PaymentService::class, $confirmService);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/orders/{$orderId}/confirm-payment");

    $response->assertSuccessful()
        ->assertJsonPath('paid', true)
        ->assertJsonPath('payment_status', 'paid');
});
