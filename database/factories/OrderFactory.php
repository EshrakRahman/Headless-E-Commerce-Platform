<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-'.fake()->unique()->randomNumber(8),
            'status' => 'pending',
            'subtotal' => 0,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 0,
            'notes' => null,
            'payment_method' => null,
            'payment_status' => 'pending',
            'shipping_address' => null,
            'billing_address' => null,
        ];
    }
}
