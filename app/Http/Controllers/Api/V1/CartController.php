<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'nullable|exists:sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $items = [];
        $total = 0;

        foreach ($request->items as $item) {
            $product = Product::with('sizes')->findOrFail($item['product_id']);
            $unitPrice = (float) $product->price;
            $sizeName = null;
            $sizeId = null;
            $available = $product->quantity;
            $inStock = true;

            if (! empty($item['size_id'])) {
                $size = $product->sizes()
                    ->where('size_id', $item['size_id'])
                    ->first();

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
