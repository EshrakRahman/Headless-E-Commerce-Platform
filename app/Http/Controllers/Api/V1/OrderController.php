<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;
use Throwable;

class OrderController extends Controller
{
    /**
     * Place a new order and create a Stripe PaymentIntent.
     *
     * @tags Orders
     *
     * @throws Throwable
     */
    public function store(StoreOrderRequest $request, PaymentService $paymentService): JsonResource
    {
        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($validated, $paymentService) {
                $user = auth()->user();

                $itemsData = [];

                $total = 0;

                foreach ($validated['items'] as $item) {
                    $product = Product::with('sizes')->findOrFail($item['product_id']);
                    $unitPrice = $product->sale_price ?? (float) $product->price;
                    $sizeName = null;

                    if (! empty($item['size_id'])) {
                        $size = $product->sizes()
                            ->where('size_id', $item['size_id'])
                            ->first();

                        if (! $size) {
                            throw ValidationException::withMessages([
                                'items.*.size_id' => "Size is not available for product '$product->name'.",
                            ]);
                        }

                        $pivot = $size->pivot;

                        if ($pivot->stock < $item['quantity']) {
                            throw ValidationException::withMessages([
                                'items.*.quantity' => "Not enough stock for $product->name - {$size->name}. Available: $pivot->stock.",
                            ]);
                        }

                        $unitPrice += (float) $pivot->additional_price;
                        $sizeName = $size->name;

                        $pivot->decrement('stock', $item['quantity']);
                    } else {
                        if ($product->quantity < $item['quantity']) {
                            throw ValidationException::withMessages([
                                'items.*.quantity' => "Not enough stock for '{$product->name}'. Available: {$product->quantity}.",
                            ]);
                        }

                        $product->decrement('quantity', $item['quantity']);
                    }

                    $subtotal = $unitPrice * $item['quantity'];
                    $total += $subtotal;

                    $itemsData[] = [
                        'product_id' => $product->id,
                        'size_id' => $item['size_id'] ?? null,
                        'product_name' => $product->name,
                        'size_name' => $sizeName,
                        'unit_price' => $unitPrice,
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal,
                    ];
                }

                $discount = 0;
                $couponCode = null;

                if (!empty($validated['coupon_code'])) {
                    $coupon = \App\Models\Coupon::active()
                        ->where('code', strtoupper(trim($validated['coupon_code'])))
                        ->first();

                    if (!$coupon) {
                        throw ValidationException::withMessages([
                            'coupon_code' => 'The coupon code is invalid or has expired.',
                        ]);
                    }

                    if (!$coupon->isValidForSubtotal($total)) {
                        throw ValidationException::withMessages([
                            'coupon_code' => "This coupon requires a minimum subtotal of $" . number_format($coupon->min_order_amount, 2) . ".",
                        ]);
                    }

                    $discount = $coupon->calculateDiscount($total);
                    $couponCode = $coupon->code;
                }

                $lastId = Order::max('id') ?? 0;

                $order = $user->orders()->create([
                    'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT),
                    'status' => 'pending',
                    'subtotal' => $total,
                    'discount' => $discount,
                    'coupon_code' => $couponCode,
                    'total' => max(0, $total - $discount),
                    'notes' => $validated['notes'] ?? null,
                    'payment_method' => $validated['payment_method'] ?? 'stripe',
                    'payment_status' => 'pending',
                    'shipping_address' => $validated['shipping_address'],
                    'billing_address' => $validated['billing_address'] ?? null,
                ]);

                $order->items()->createMany($itemsData);

                $intent = $paymentService->createIntent($order);

                $order->update([
                    'payment_intent_id' => $intent['payment_intent_id'],
                ]);

                $order->payment_intent_client_secret = $intent['client_secret'];

                $order->load('items');

                return new OrderResource($order);
            });
        } catch (ApiErrorException $e) {
            throw ValidationException::withMessages([
                'payment' => 'Payment processing failed. Please try again.',
            ]);
        }
    }

    /**
     * List the authenticated user's orders.
     *
     * @tags Orders
     */
    public function index(): AnonymousResourceCollection
    {
        $orders = auth()->user()
            ->orders()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    /**
     * Get a single order with its items.
     *
     * @tags Orders
     */
    public function show(Order $order): JsonResource
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items');

        return new OrderResource($order);
    }

    /**
     * Retry payment for a failed order by creating a new PaymentIntent.
     *
     * @tags Orders
     *
     * @throws ApiErrorException
     */
    public function retryPayment(Order $order, PaymentService $paymentService): JsonResponse
    {
        abort_if($order->user_id !== auth()->id(), 403);
        abort_if($order->payment_status?->value === 'paid', 422, 'Order is already paid.');

        try {
            // Cancel the old intent if it's still actionable
            if ($order->payment_intent_id !== null) {
                $paymentService->cancelIntent($order->payment_intent_id);
            }

            $intent = $paymentService->createIntent($order);

            $order->update([
                'payment_intent_id' => $intent['payment_intent_id'],
                'payment_status' => 'pending',
            ]);

            return response()->json([
                'payment_intent_id' => $intent['payment_intent_id'],
                'payment_intent_client_secret' => $intent['client_secret'],
            ]);
        } catch (ApiErrorException $e) {
            throw ValidationException::withMessages([
                'payment' => 'Payment processing failed. Please try again.',
            ]);
        }
    }

    /**
     * Check the payment status of an order.
     *
     * @tags Orders
     */
    public function paymentStatus(Order $order): JsonResponse
    {
        abort_if($order->user_id !== auth()->id(), 403);

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
        ]);
    }

    /**
     * Confirm the payment by checking the PaymentIntent status directly with Stripe.
     *
     * @tags Orders
     *
     * @throws ApiErrorException
     */
    public function confirmPayment(Order $order, PaymentService $paymentService): JsonResponse
    {
        abort_if($order->user_id !== auth()->id(), 403);

        try {
            $result = $paymentService->confirmPayment($order);

            return response()->json([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'paid' => $result['paid'],
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'stripe_status' => $result['payment_intent_status'],
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'message' => 'Unable to verify payment. Please try again.',
            ], 502);
        }
    }
}
