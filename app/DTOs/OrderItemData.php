<?php

namespace App\DTOs;

class OrderItemData
{
    /**
     * Create a new OrderItemData instance.
     */
    public function __construct(
        public int $productId,
        public ?int $sizeId,
        public int $quantity
    ) {}

    /**
     * Create an instance from an array.
     *
     * @param  array{product_id: int, size_id: ?int, quantity: int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            sizeId: $data['size_id'] ?? null,
            quantity: $data['quantity']
        );
    }
}
