<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\PreviewCartRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Preview a cart — check stock availability and calculate prices.
     *
     * @tags Cart
     */
    public function preview(PreviewCartRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $productIds = collect($validated['items'])->pluck('product_id')->unique()->toArray();

        // Eager load sizes, batch fetching all products at once to avoid N+1
        $products = Product::with('sizes')->findOrFail($productIds)->keyBy('id');

        $items = [];
        $total = 0;

        foreach ($validated['items'] as $item) {
            /** @var Product $product */
            $product = $products->get($item['product_id']);
            $unitPrice = $product->sale_price ?? (float) $product->price;
            $sizeName = null;
            $sizeId = null;
            $available = $product->quantity;
            $inStock = true;

            if (! empty($item['size_id'])) {
                // In-memory filter on the already eager-loaded sizes relation
                $size = $product->sizes->firstWhere('id', $item['size_id']);

                if ($size) {
                    $unitPrice += (float) $size->pivot->additional_price;
                    $sizeName = $size->name;
                    $sizeId = $size->id;
                    $available = $size->pivot->stock;
                    $inStock = $size->pivot->stock >= $item['quantity'];
                } else {
                    $inStock = false;
                }
            } else {
                $inStock = $product->quantity >= $item['quantity'];
            }

            $subtotal = $unitPrice * $item['quantity'];
            $total += $subtotal;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'size_id' => $sizeId,
                'size_name' => $sizeName,
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
                'in_stock' => $inStock,
                'available_stock' => $available,
            ];
        }

        return response()->json([
            'items' => $items,
            'subtotal' => $total,
            'total' => $total,
        ]);
    }
}
