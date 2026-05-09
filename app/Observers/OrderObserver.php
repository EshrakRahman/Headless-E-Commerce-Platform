<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\InventoryService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        $newStatus = $order->status;

        if ($newStatus === OrderStatus::Cancelled) {
            app(InventoryService::class)->restoreForCancelledOrder($order);
        }
    }
}
