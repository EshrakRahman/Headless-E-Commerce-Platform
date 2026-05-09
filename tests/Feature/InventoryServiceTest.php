<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── adjustStock: Simple Product ────────────────────────────────────

it('adds stock to a simple product and records movement', function () {
    $product = Product::factory()->create(['quantity' => 10]);

    app(InventoryService::class)->adjustStock(
        product: $product,
        quantityChange: 5,
        type: 'adjustment',
        reason: 'Restock',
    );

    expect($product->fresh()->quantity)->toBe(15);

    $movement = StockMovement::where('product_id', $product->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe('adjustment')
        ->and($movement->quantity_change)->toBe(5)
        ->and($movement->before_quantity)->toBe(10)
        ->and($movement->after_quantity)->toBe(15)
        ->and($movement->reason)->toBe('Restock');
});

it('removes stock from a simple product and records movement', function () {
    $product = Product::factory()->create(['quantity' => 10]);

    app(InventoryService::class)->adjustStock(
        product: $product,
        quantityChange: -3,
        type: 'adjustment',
        reason: 'Damaged',
    );

    expect($product->fresh()->quantity)->toBe(7);

    $movement = StockMovement::where('product_id', $product->id)->first();
    expect($movement->quantity_change)->toBe(-3)
        ->and($movement->before_quantity)->toBe(10)
        ->and($movement->after_quantity)->toBe(7);
});

it('clamps stock to zero and never goes negative', function () {
    $product = Product::factory()->create(['quantity' => 5]);

    app(InventoryService::class)->adjustStock(
        product: $product,
        quantityChange: -100,
        type: 'adjustment',
        reason: 'Removed too many',
    );

    expect($product->fresh()->quantity)->toBe(0);

    $movement = StockMovement::where('product_id', $product->id)->first();
    expect($movement->quantity_change)->toBe(-5)
        ->and($movement->after_quantity)->toBe(0);
});

// ─── adjustStock: Sized Product ─────────────────────────────────────

it('adds stock to a sized product and records movement', function () {
    $product = Product::factory()->create(['quantity' => null]);
    $size = Size::factory()->create();
    $productSize = ProductSize::create([
        'product_id' => $product->id,
        'size_id' => $size->id,
        'stock' => 5,
    ]);

    app(InventoryService::class)->adjustStock(
        product: $product,
        quantityChange: 10,
        type: 'adjustment',
        reason: 'Restock size',
        productSize: $productSize,
    );

    expect($productSize->fresh()->stock)->toBe(15);

    $movement = StockMovement::where('product_size_id', $productSize->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->product_id)->toBe($product->id)
        ->and($movement->quantity_change)->toBe(10)
        ->and($movement->before_quantity)->toBe(5)
        ->and($movement->after_quantity)->toBe(15);
});

it('clamps sized product stock to zero', function () {
    $product = Product::factory()->create(['quantity' => null]);
    $size = Size::factory()->create();
    $productSize = ProductSize::create([
        'product_id' => $product->id,
        'size_id' => $size->id,
        'stock' => 3,
    ]);

    app(InventoryService::class)->adjustStock(
        product: $product,
        quantityChange: -10,
        type: 'adjustment',
        reason: 'Removed too many',
        productSize: $productSize,
    );

    expect($productSize->fresh()->stock)->toBe(0);
});

// ─── reserveForOrder ────────────────────────────────────────────────

it('reserves stock for order items on simple products', function () {
    $product = Product::factory()->create(['quantity' => 20]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => null,
        'quantity' => 5,
    ]);

    app(InventoryService::class)->reserveForOrder($order);

    expect($product->fresh()->quantity)->toBe(15);

    $movement = StockMovement::where('order_id', $order->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe('order')
        ->and($movement->quantity_change)->toBe(-5)
        ->and($movement->product_id)->toBe($product->id)
        ->and($movement->product_size_id)->toBeNull();
});

it('reserves stock for order items on sized products', function () {
    $product = Product::factory()->create(['quantity' => null]);
    $size = Size::factory()->create();
    $productSize = ProductSize::create([
        'product_id' => $product->id,
        'size_id' => $size->id,
        'stock' => 10,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => $size->id,
        'quantity' => 3,
    ]);

    app(InventoryService::class)->reserveForOrder($order);

    expect($productSize->fresh()->stock)->toBe(7);

    $movement = StockMovement::where('order_id', $order->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->product_size_id)->toBe($productSize->id)
        ->and($movement->quantity_change)->toBe(-3);
});

it('reserves stock for multiple order items atomically', function () {
    $product1 = Product::factory()->create(['quantity' => 20]);
    $product2 = Product::factory()->create(['quantity' => 30]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'size_id' => null,
        'quantity' => 5,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'size_id' => null,
        'quantity' => 10,
    ]);

    app(InventoryService::class)->reserveForOrder($order);

    expect($product1->fresh()->quantity)->toBe(15)
        ->and($product2->fresh()->quantity)->toBe(20);

    expect(StockMovement::where('order_id', $order->id)->count())->toBe(2);
});

// ─── restoreForCancelledOrder ───────────────────────────────────────

it('restores stock when order is cancelled', function () {
    $product = Product::factory()->create(['quantity' => 20]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => null,
        'quantity' => 5,
    ]);

    app(InventoryService::class)->reserveForOrder($order);
    expect($product->fresh()->quantity)->toBe(15);

    $order->update(['status' => OrderStatus::Cancelled]);
    app(InventoryService::class)->restoreForCancelledOrder($order);

    expect($product->fresh()->quantity)->toBe(20);

    $refundMovement = StockMovement::where('order_id', $order->id)
        ->where('type', 'refund')
        ->first();
    expect($refundMovement)->not->toBeNull()
        ->and($refundMovement->quantity_change)->toBe(5)
        ->and($refundMovement->after_quantity)->toBe(20);
});

it('is idempotent when restoring cancelled order multiple times', function () {
    $product = Product::factory()->create(['quantity' => 20]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => null,
        'quantity' => 5,
    ]);

    app(InventoryService::class)->reserveForOrder($order);

    // Restore twice
    app(InventoryService::class)->restoreForCancelledOrder($order);
    app(InventoryService::class)->restoreForCancelledOrder($order);

    expect($product->fresh()->quantity)->toBe(20);

    // Only one refund movement
    expect(StockMovement::where('order_id', $order->id)
        ->where('type', 'refund')
        ->count()
    )->toBe(1);
});

it('does nothing when restoring an order that was never reserved', function () {
    $product = Product::factory()->create(['quantity' => 20]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => null,
        'quantity' => 5,
    ]);

    app(InventoryService::class)->restoreForCancelledOrder($order);

    expect($product->fresh()->quantity)->toBe(20);
    expect(StockMovement::count())->toBe(0);
});

it('restores stock for sized products when order cancelled', function () {
    $product = Product::factory()->create(['quantity' => null]);
    $size = Size::factory()->create();
    $productSize = ProductSize::create([
        'product_id' => $product->id,
        'size_id' => $size->id,
        'stock' => 10,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => $size->id,
        'quantity' => 3,
    ]);

    app(InventoryService::class)->reserveForOrder($order);
    expect($productSize->fresh()->stock)->toBe(7);

    app(InventoryService::class)->restoreForCancelledOrder($order);
    expect($productSize->fresh()->stock)->toBe(10);

    $refund = StockMovement::where('order_id', $order->id)
        ->where('type', 'refund')
        ->first();
    expect($refund->product_size_id)->toBe($productSize->id);
});

// ─── Order Observer Integration ─────────────────────────────────────

it('auto-restores stock when order status changes to cancelled', function () {
    $product = Product::factory()->create(['quantity' => 20]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => null,
        'quantity' => 5,
    ]);

    app(InventoryService::class)->reserveForOrder($order);
    expect($product->fresh()->quantity)->toBe(15);

    $order->update(['status' => OrderStatus::Cancelled]);

    expect($product->fresh()->quantity)->toBe(20);

    $refund = StockMovement::where('order_id', $order->id)
        ->where('type', 'refund')
        ->first();
    expect($refund)->not->toBeNull();
});

it('does not restore stock when order status changes to non-cancelled statuses', function () {
    $product = Product::factory()->create(['quantity' => 20]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size_id' => null,
        'quantity' => 5,
    ]);

    app(InventoryService::class)->reserveForOrder($order);
    expect($product->fresh()->quantity)->toBe(15);

    $order->update(['status' => OrderStatus::Confirmed]);
    expect($product->fresh()->quantity)->toBe(15); // No restore

    $order->update(['status' => OrderStatus::Shipped]);
    expect($product->fresh()->quantity)->toBe(15); // No restore

    expect(StockMovement::where('order_id', $order->id)
        ->where('type', 'refund')
        ->count()
    )->toBe(0);
});
