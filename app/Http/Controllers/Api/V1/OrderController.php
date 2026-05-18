<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(StoreOrderRequest $request): JsonResource
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            $user = auth()->user();

            $itemsData = [];

            $total = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::with('sizes')->findOrFail($item['product_id']);
                $unitPrice = $product->sale_price ?? (float) $product->price;
                $sizeName = null;

                if (! empty($item['size_id'])) {
                    $size = $product->sizes()
                        ->where('size_id', $item['size_id'])
                        ->first();

                    if (! $size) {
                        throw ValidationException::withMessages([
                            'items.*.size_id' => "Size is not available for product '$product->name'.",
                        ]);
                    }

                    $pivot = $size->pivot;

                    if ($pivot->stock < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items.*.quantity' => "Not enough stock for $product->name - {$size->name}. Available: $pivot->stock.",
                        ]);
                    }

                    $unitPrice += (float) $pivot->additional_price;
                    $sizeName = $size->name;

                    $pivot->decrement('stock', $item['quantity']);
                } else {
                    if ($product->quantity < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items.*.quantity' => "Not enough stock for '{$product->name}'. Available: {$product->quantity}.",
                        ]);
                    }

                    $product->decrement('quantity', $item['quantity']);
                }

                $subtotal = $unitPrice * $item['quantity'];
                $total += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'size_id' => $item['size_id'] ?? null,
                    'product_name' => $product->name,
                    'size_name' => $sizeName,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ];
            }

            $lastId = Order::max('id') ?? 0;

            $order = $user->orders()->create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT),
                'status' => 'pending',
                'subtotal' => $total,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'] ?? null,
            ]);

            $order->items()->createMany($itemsData);

            $order->load('items');

            return new OrderResource($order);
        });
    }

    public function index(): AnonymousResourceCollection
    {
        $orders = auth()->user()
            ->orders()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): JsonResource
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items');

        return new OrderResource($order);
    }
}
