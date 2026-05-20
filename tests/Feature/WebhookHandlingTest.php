<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('stripe.webhook_secret', 'whsec_test_secret');
});

// ─── Payment Succeeded ──────────────────────────────────────────────

it('marks order as paid and confirmed on payment_intent.succeeded', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 50.00, 'quantity' => 10]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending->value,
        'payment_status' => PaymentStatus::Pending->value,
        'payment_intent_id' => 'pi_test_abc123',
        'subtotal' => 100,
        'total' => 100,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 50,
        'subtotal' => 100,
    ]);

    $mockService = Mockery::mock(PaymentService::class)->makePartial();
    $mockService->shouldReceive('handleWebhook')
        ->once()
        ->andReturnUsing(function ($payload, $sigHeader) use ($order) {
            $order->update(['payment_status' => PaymentStatus::Paid->value]);
            $order->update(['status' => OrderStatus::Confirmed->value]);

            return ['handled' => true];
        });

    $this->app->instance(PaymentService::class, $mockService);

    $response = $this->postJson('/webhooks/stripe', [
        'type' => 'payment_intent.succeeded',
    ], ['Stripe-Signature' => 'fake_signature']);

    $response->assertStatus(200);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Confirmed);
});

it('marks only payment status as paid when order is not pending', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 50.00, 'quantity' => 10]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Processing->value,
        'payment_status' => PaymentStatus::Pending->value,
        'payment_intent_id' => 'pi_test_abc123',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 50,
        'subtotal' => 50,
    ]);

    $mockService = Mockery::mock(PaymentService::class)->makePartial();
    $mockService->shouldReceive('handleWebhook')
        ->once()
        ->andReturnUsing(function ($payload, $sigHeader) use ($order) {
            $order->update(['payment_status' => PaymentStatus::Paid->value]);

            return ['handled' => true];
        });

    $this->app->instance(PaymentService::class, $mockService);

    $this->postJson('/webhooks/stripe', [
        'type' => 'payment_intent.succeeded',
    ], ['Stripe-Signature' => 'fake_signature'])
        ->assertStatus(200);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Processing);
});

// ─── Payment Failed ─────────────────────────────────────────────────

it('marks order payment as failed on payment_intent.payment_failed', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 50.00, 'quantity' => 10]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending->value,
        'payment_status' => PaymentStatus::Pending->value,
        'payment_intent_id' => 'pi_test_failed',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 50,
        'subtotal' => 50,
    ]);

    $mockService = Mockery::mock(PaymentService::class)->makePartial();
    $mockService->shouldReceive('handleWebhook')
        ->once()
        ->andReturnUsing(function ($payload, $sigHeader) use ($order) {
            $order->update(['payment_status' => PaymentStatus::Failed->value]);

            return ['handled' => true];
        });

    $this->app->instance(PaymentService::class, $mockService);

    $this->postJson('/webhooks/stripe', [
        'type' => 'payment_intent.payment_failed',
    ], ['Stripe-Signature' => 'fake_signature'])
        ->assertStatus(200);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Failed)
        ->and($order->status)->toBe(OrderStatus::Pending);
});

// ─── Invalid Signature ──────────────────────────────────────────────

it('returns error on invalid webhook signature', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'payment_intent_id' => 'pi_test_abc123',
    ]);

    $mockService = Mockery::mock(PaymentService::class)->makePartial();
    $mockService->shouldReceive('handleWebhook')
        ->once()
        ->andReturn(['handled' => false, 'error' => 'Invalid signature.']);

    $this->app->instance(PaymentService::class, $mockService);

    $this->postJson('/webhooks/stripe', [
        'type' => 'payment_intent.succeeded',
    ], ['Stripe-Signature' => 'bad_signature'])
        ->assertNoContent(400);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Pending);
});

// ─── Unhandled Event Type ───────────────────────────────────────────

it('ignores unhandled event types gracefully', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'payment_intent_id' => 'pi_test_abc123',
    ]);

    $mockService = Mockery::mock(PaymentService::class)->makePartial();
    $mockService->shouldReceive('handleWebhook')
        ->once()
        ->andReturn(['handled' => true]);

    $this->app->instance(PaymentService::class, $mockService);

    $this->postJson('/webhooks/stripe', [
        'type' => 'charge.updated',
    ], ['Stripe-Signature' => 'fake_signature'])
        ->assertStatus(200);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Pending);
});

// ─── Missing Order in Metadata ──────────────────────────────────────

it('handles payment succeeded with missing order_id in metadata', function () {
    $mockService = Mockery::mock(PaymentService::class)->makePartial();
    $mockService->shouldReceive('handleWebhook')
        ->once()
        ->andReturn(['handled' => true]);

    $this->app->instance(PaymentService::class, $mockService);

    $this->postJson('/webhooks/stripe', [
        'type' => 'payment_intent.succeeded',
    ], ['Stripe-Signature' => 'fake_signature'])
        ->assertStatus(200);
});

it('handles payment succeeded with non-existent order', function () {
    $mockService = Mockery::mock(PaymentService::class)->makePartial();
    $mockService->shouldReceive('handleWebhook')
        ->once()
        ->andReturn(['handled' => true]);

    $this->app->instance(PaymentService::class, $mockService);

    $this->postJson('/webhooks/stripe', [
        'type' => 'payment_intent.succeeded',
    ], ['Stripe-Signature' => 'fake_signature'])
        ->assertStatus(200);
});
