<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'product_size_id',
        'type',
        'before_quantity',
        'quantity_change',
        'after_quantity',
        'reason',
        'user_id',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'before_quantity' => 'integer',
            'quantity_change' => 'integer',
            'after_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
