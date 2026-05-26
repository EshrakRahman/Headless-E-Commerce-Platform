<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'category_id', 'brand_id', 'description', 'image', 'slug', 'price', 'compare_price', 'quantity', 'is_featured'])]
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function getAvgRatingAttribute(): ?float
    {
        $avg = $this->relationLoaded('approvedReviews')
            ? $this->approvedReviews->avg('rating')
            : $this->approvedReviews()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function getSalePriceAttribute(): ?float
    {
        $now = now();
        $discounts = $this->relationLoaded('discounts')
            ? $this->discounts
            : $this->discounts()->active()->get();

        $activeDiscount = $discounts
            ->filter(fn ($discount) => $discount->is_active &&
                ($discount->starts_at === null || $discount->starts_at->lte($now)) &&
                ($discount->ends_at === null || $discount->ends_at->gte($now))
            )
            ->sortByDesc(fn ($discount) => $discount->type === DiscountType::Percentage
                ? (float) $discount->value * (float) $this->price / 100
                : (float) $discount->value
            )
            ->first();

        if ($activeDiscount === null) {
            return null;
        }

        return $activeDiscount->type === DiscountType::Percentage
            ? round((float) $this->price * (1 - (float) $activeDiscount->value / 100), 2)
            : max(0, (float) $this->price - (float) $activeDiscount->value);
    }
}
