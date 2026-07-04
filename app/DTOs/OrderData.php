<?php

namespace App\DTOs;

use App\Http\Requests\Api\V1\Order\StoreOrderRequest;

class OrderData
{
    /**
     * Create a new OrderData instance.
     *
     * @param  OrderItemData[]  $items
     * @param  array<string, mixed>|null  $shippingAddress
     * @param  array<string, mixed>|null  $billingAddress
     */
    public function __construct(
        public array $items,
        public ?array $shippingAddress = null,
        public ?array $billingAddress = null,
        public ?string $notes = null,
        public ?string $couponCode = null,
        public string $paymentMethod = 'stripe'
    ) {}

    /**
     * Create an instance from a StoreOrderRequest.
     */
    public static function fromRequest(StoreOrderRequest $request): self
    {
        $validated = $request->validated();

        $items = array_map(
            fn (array $item): OrderItemData => OrderItemData::fromArray($item),
            $validated['items']
        );

        return new self(
            items: $items,
            shippingAddress: $validated['shipping_address'],
            billingAddress: $validated['billing_address'] ?? null,
            notes: $validated['notes'] ?? null,
            couponCode: $validated['coupon_code'] ?? null,
            paymentMethod: $validated['payment_method'] ?? 'stripe'
        );
    }
}
