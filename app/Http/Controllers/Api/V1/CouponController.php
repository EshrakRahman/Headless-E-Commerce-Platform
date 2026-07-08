<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coupon\ApplyCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function apply(ApplyCouponRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $code = strtoupper(trim($validated['code']));
        $subtotal = (float) $validated['subtotal'];

        $coupon = Coupon::active()->where('code', $code)->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'code' => 'The coupon code is invalid or has expired.',
            ]);
        }

        if ($coupon->isUsedUp()) {
            throw ValidationException::withMessages([
                'code' => 'This coupon has reached its usage limit.',
            ]);
        }

        if ($coupon->isUsedUpForUser($request->user())) {
            throw ValidationException::withMessages([
                'code' => 'You have already used this coupon the maximum number of times.',
            ]);
        }

        if (! $coupon->isValidForSubtotal($subtotal)) {
            throw ValidationException::withMessages([
                'code' => 'This coupon requires a minimum subtotal of $'.number_format($coupon->min_order_amount, 2).'.',
            ]);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return response()->json([
            'code' => $coupon->code,
            'type' => $coupon->type->value,
            'value' => (float) $coupon->value,
            'discount_amount' => $discount,
            'min_order_amount' => $coupon->min_order_amount ? (float) $coupon->min_order_amount : null,
            'description' => $coupon->description,
        ]);
    }
}
