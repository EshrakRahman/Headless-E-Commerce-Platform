<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'starts_at',
        'ends_at',
        'is_active',
        'description',
        'usage_limit',
        'usage_limit_per_user',
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
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
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

    public function isUsedUp(): bool
    {
        if ($this->usage_limit === null) {
            return false;
        }

        $usedCount = Order::where('coupon_code', $this->code)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->count();

        return $usedCount >= $this->usage_limit;
    }

    public function isUsedUpForUser(?User $user): bool
    {
        if ($this->usage_limit_per_user === null || $user === null) {
            return false;
        }

        $usedCount = Order::where('coupon_code', $this->code)
            ->where('user_id', $user->id)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->count();

        return $usedCount >= $this->usage_limit_per_user;
    }
}
