<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'category_id', 'description', 'image', 'slug', 'price', 'compare_price', 'quantity', 'is_featured'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    public function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_size')
            ->withPivot('additional_price', 'stock')
            ->withTimestamps();
    }

    public function productSizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class);
    }

    public function getSalePriceAttribute(): ?float
    {
        $activeDiscount = $this->discounts()
            ->active()
            ->get()
            ->sortByDesc(fn ($discount) => $discount->type === 'percentage'
                ? $discount->value * $this->price / 100
                : $discount->value
            )
            ->first();

        if ($activeDiscount === null) {
            return null;
        }

        return $activeDiscount->type === 'percentage'
            ? round($this->price * (1 - $activeDiscount->value / 100), 2)
            : max(0, $this->price - $activeDiscount->value);
    }
}
