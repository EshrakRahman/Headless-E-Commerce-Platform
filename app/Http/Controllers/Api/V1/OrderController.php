<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\OrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
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
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResource
    {
        $order = $orderService->createOrder(
            $request->user(),
            OrderData::fromRequest($request)
        );

        return new OrderResource($order);
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
