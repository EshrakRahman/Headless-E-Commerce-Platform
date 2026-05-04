<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductSize extends Pivot
{
    protected $table = 'product_size';

    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'size_id',
        'additional_price',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
