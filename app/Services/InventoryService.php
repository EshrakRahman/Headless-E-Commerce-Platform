<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function adjustStock(
        Product $product,
        int $quantityChange,
        string $type,
        ?string $reason = null,
        ?ProductSize $productSize = null,
        ?Order $order = null,
        ?User $user = null,
    ): void {
        DB::transaction(function () use ($product, $quantityChange, $type, $reason, $productSize, $order, $user) {
            if ($productSize !== null) {
                $before = $productSize->stock ?? 0;
                $after = max(0, $before + $quantityChange);
                $actualChange = $after - $before;

                $productSize->update(['stock' => $after]);
            } else {
                $before = $product->quantity ?? 0;
                $after = max(0, $before + $quantityChange);
                $actualChange = $after - $before;

                $product->update(['quantity' => $after]);
            }

            StockMovement::create([
                'product_id' => $product->id,
                'product_size_id' => $productSize?->id,
                'type' => $type,
                'before_quantity' => $before,
                'quantity_change' => $actualChange,
                'after_quantity' => $after,
                'reason' => $reason,
                'user_id' => $user?->id,
                'order_id' => $order?->id,
            ]);
        });
    }

    public function reserveForOrder(Order $order, ?User $user = null): void
    {
        $order->loadMissing('items.product', 'items.size');

        foreach ($order->items as $item) {
            $product = $item->product;

            if ($product === null) {
                continue;
            }

            if ($item->size_id !== null) {
                $productSize = ProductSize::where('product_id', $item->product_id)
                    ->where('size_id', $item->size_id)
                    ->first();

                if ($productSize === null) {
                    continue;
                }

                $this->adjustStock(
                    product: $product,
                    quantityChange: -$item->quantity,
                    type: 'order',
                    productSize: $productSize,
                    order: $order,
                    user: $user,
                );
            } else {
                $this->adjustStock(
                    product: $product,
                    quantityChange: -$item->quantity,
                    type: 'order',
                    order: $order,
                    user: $user,
                );
            }
        }
    }

    public function restoreForCancelledOrder(Order $order, ?User $user = null): void
    {
        $alreadyRestored = StockMovement::where('order_id', $order->id)
            ->where('type', 'refund')
            ->exists();

        if ($alreadyRestored) {
            return;
        }

        $orderMovements = StockMovement::where('order_id', $order->id)
            ->where('type', 'order')
            ->get();

        if ($orderMovements->isEmpty()) {
            return;
        }

        foreach ($orderMovements as $movement) {
            $product = $movement->product;

            if ($product === null) {
                continue;
            }

            $this->adjustStock(
                product: $product,
                quantityChange: abs($movement->quantity_change),
                type: 'refund',
                reason: 'Order cancelled',
                productSize: $movement->productSize,
                order: $order,
                user: $user,
            );
        }
    }
}
