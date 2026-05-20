<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Create a PaymentIntent for the given order.
     *
     * @return array{client_secret: string, payment_intent_id: string}
     *
     * @throws ApiErrorException
     */
    public function createIntent(Order $order): array
    {
        $amount = (int) round($order->total * 100);

        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => config('stripe.currency', 'usd'),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    /**
     * Cancel an uncaptured PaymentIntent.
     *
     * @throws ApiErrorException
     */
    public function cancelIntent(string $paymentIntentId): void
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        if (in_array($intent->status, ['requires_payment_method', 'requires_capture', 'requires_confirmation', 'requires_action'], true)) {
            $intent->cancel();
        }
    }

    /**
     * Issue a full refund for the order's PaymentIntent.
     *
     * @throws ApiErrorException
     */
    public function refund(Order $order): void
    {
        if ($order->payment_intent_id === null) {
            return;
        }

        $intent = PaymentIntent::retrieve($order->payment_intent_id);

        if (! empty($intent->latest_charge)) {
            $intent->latest_charge->refund();
        }
    }

    /**
     * Confirm an order's payment by checking the PaymentIntent status with Stripe.
     *
     * @return array{paid: bool, status: string, payment_intent_status: string}
     *
     * @throws ApiErrorException
     */
    public function confirmPayment(Order $order): array
    {
        if ($order->payment_intent_id === null) {
            return [
                'paid' => false,
                'status' => $order->status->value,
                'payment_intent_status' => 'no_intent',
            ];
        }

        $intent = PaymentIntent::retrieve($order->payment_intent_id);

        if ($intent->status === 'succeeded') {
            $order->update([
                'payment_status' => 'paid',
            ]);

            if ($order->status->value === 'pending') {
                $order->update([
                    'status' => 'confirmed',
                ]);
            }

            return [
                'paid' => true,
                'status' => $order->status->value,
                'payment_intent_status' => $intent->status,
            ];
        }

        if ($intent->status === 'requires_payment_method') {
            $order->update([
                'payment_status' => 'failed',
            ]);

            return [
                'paid' => false,
                'status' => $order->status->value,
                'payment_intent_status' => $intent->status,
            ];
        }

        // Still processing — return current state
        return [
            'paid' => false,
            'status' => $order->status->value,
            'payment_intent_status' => $intent->status,
        ];
    }

    public function handleWebhook(string $payload, string $sigHeader): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed.', [
                'error' => $e->getMessage(),
            ]);

            return ['handled' => false, 'error' => 'Invalid signature.'];
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object()),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object()),
            default => null,
        };

        return ['handled' => true];
    }

    /**
     * Handle a successful payment.
     */
    private function handlePaymentSucceeded(PaymentIntent $intent): void
    {
        $orderId = $intent->metadata['order_id'] ?? null;

        if ($orderId === null) {
            Log::warning('Stripe payment_intent.succeeded missing order_id metadata.', [
                'payment_intent_id' => $intent->id,
            ]);

            return;
        }

        $order = Order::find($orderId);

        if ($order === null) {
            Log::warning('Order not found for payment_intent.succeeded.', [
                'order_id' => $orderId,
                'payment_intent_id' => $intent->id,
            ]);

            return;
        }

        $order->update([
            'payment_status' => 'paid',
        ]);

        // Move order to confirmed if still pending
        if ($order->status->value === 'pending') {
            $order->update([
                'status' => 'confirmed',
            ]);
        }
    }

    /**
     * Handle a failed payment.
     */
    private function handlePaymentFailed(PaymentIntent $intent): void
    {
        $orderId = $intent->metadata['order_id'] ?? null;

        if ($orderId === null) {
            Log::warning('Stripe payment_intent.payment_failed missing order_id metadata.', [
                'payment_intent_id' => $intent->id,
            ]);

            return;
        }

        $order = Order::find($orderId);

        if ($order === null) {
            Log::warning('Order not found for payment_intent.payment_failed.', [
                'order_id' => $orderId,
                'payment_intent_id' => $intent->id,
            ]);

            return;
        }

        $order->update([
            'payment_status' => 'failed',
        ]);
    }
}
