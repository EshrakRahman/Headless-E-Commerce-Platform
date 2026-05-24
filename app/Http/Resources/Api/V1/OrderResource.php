<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'size_name' => $item->size_name,
                'quantity' => $item->quantity,
                'unit_price' => $this->formatPrice($item->unit_price),
                'subtotal' => $this->formatPrice($item->subtotal),
            ])),
            'subtotal' => $this->formatPrice($this->subtotal),
            'shipping_cost' => $this->formatPrice($this->shipping_cost),
            'discount' => $this->formatPrice($this->discount),
            'coupon_code' => $this->coupon_code,
            'total' => $this->formatPrice($this->total),
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'notes' => $this->notes,
            'payment_status' => $this->payment_status,
            'payment_intent_id' => $this->payment_intent_id,
            'payment_intent_client_secret' => $this->when(
                $this->payment_intent_client_secret !== null,
                $this->payment_intent_client_secret,
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function formatPrice(?float $value): ?float
    {
        return $value !== null ? (float) number_format($value, 2, '.', '') : null;
    }
}
