<?php

namespace App\Services;

use App\DTOs\OrderData;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create a new OrderService instance.
     */
    public function __construct(
        protected PaymentService $paymentService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Place a new order and create a Stripe PaymentIntent.
     *
     * @throws ValidationException
     * @throws \Throwable
     */
    public function createOrder(User $user, OrderData $orderData): Order
    {
        // 1. Validate stock, apply coupon, create order and items inside a DB transaction.
        $order = DB::transaction(function () use ($user, $orderData): Order {
            $productIds = collect($orderData->items)->pluck('productId')->unique()->toArray();

            // Eager load sizes, locking rows for update to ensure safe concurrent stock checks
            $products = Product::with('sizes')
                ->lockForUpdate()
                ->findOrFail($productIds)
                ->keyBy('id');

            $itemsData = [];
            $total = 0;

            foreach ($orderData->items as $index => $item) {
                /** @var Product $product */
                $product = $products->get($item->productId);
                $unitPrice = $product->sale_price ?? (float) $product->price;
                $sizeName = null;

                if (! empty($item->sizeId)) {
                    $size = $product->sizes->firstWhere('id', $item->sizeId);

                    if (! $size) {
                        throw ValidationException::withMessages([
                            "items.{$index}.size_id" => "Size is not available for product '$product->name'.",
                        ]);
                    }

                    $pivot = $size->pivot;

                    if ($pivot->stock < $item->quantity) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => "Not enough stock for $product->name - {$size->name}. Available: $pivot->stock.",
                        ]);
                    }

                    $unitPrice += (float) $pivot->additional_price;
                    $sizeName = $size->name;
                } else {
                    if ($product->quantity < $item->quantity) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => "Not enough stock for '{$product->name}'. Available: {$product->quantity}.",
                        ]);
                    }
                }

                $subtotal = $unitPrice * $item->quantity;
                $total += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'size_id' => $item->sizeId,
                    'product_name' => $product->name,
                    'size_name' => $sizeName,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                ];
            }

            // Process Coupon code
            $discount = 0;
            $couponCode = null;

            if (! empty($orderData->couponCode)) {
                $coupon = Coupon::active()
                    ->where('code', strtoupper(trim($orderData->couponCode)))
                    ->first();

                if (! $coupon) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'The coupon code is invalid or has expired.',
                    ]);
                }

                if (! $coupon->isValidForSubtotal($total)) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'This coupon requires a minimum subtotal of $'.number_format($coupon->min_order_amount, 2).'.',
                    ]);
                }

                $discount = $coupon->calculateDiscount($total);
                $couponCode = $coupon->code;
            }

            // Generate order number safely by locking the table sequence
            $lastOrder = Order::lockForUpdate()->orderBy('id', 'desc')->first();
            $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
            $orderNumber = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

            // Create order record
            /** @var Order $order */
            $order = $user->orders()->create([
                'order_number' => $orderNumber,
                'status' => 'pending',
                'subtotal' => $total,
                'discount' => $discount,
                'coupon_code' => $couponCode,
                'total' => max(0, $total - $discount),
                'notes' => $orderData->notes,
                'payment_method' => $orderData->paymentMethod,
                'payment_status' => 'pending',
                'shipping_address' => $orderData->shippingAddress,
                'billing_address' => $orderData->billingAddress,
            ]);

            // Save line items
            $order->items()->createMany($itemsData);

            // Call InventoryService to reserve stock and write movements
            $this->inventoryService->reserveForOrder($order, $user);

            return $order;
        });

        // 2. Stripe integration executes outside of database transactions
        if ($orderData->paymentMethod === 'stripe') {
            try {
                $intent = $this->paymentService->createIntent($order);

                $order->update([
                    'payment_intent_id' => $intent['payment_intent_id'],
                ]);

                $order->payment_intent_client_secret = $intent['client_secret'];
            } catch (\Throwable $e) {
                $order->update([
                    'payment_status' => 'failed',
                ]);

                throw ValidationException::withMessages([
                    'payment' => 'Payment processing failed. Please try again.',
                ]);
            }
        }

        $order->load('items');

        return $order;
    }
}
