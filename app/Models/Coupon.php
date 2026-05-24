<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'starts_at',
        'ends_at',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function isValidForSubtotal(float $subtotal): bool
    {
        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) {
            return false;
        }
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        return match ($this->type) {
            DiscountType::Percentage => round($subtotal * ((float) $this->value / 100), 2),
            DiscountType::Fixed => min($subtotal, (float) $this->value),
        };
    }
}
