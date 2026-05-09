<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 100);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'size_id' => null,
            'product_name' => fake()->words(2, true),
            'size_name' => null,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ];
    }
}
