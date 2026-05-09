<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'subtotal', 'order_number',
    'status', 'shipping_cost',
    'discount', 'total', 'notes',
    'payment_method', 'payment_status',
    'shipping_address', 'billing_address',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
