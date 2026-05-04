<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = auth()->user()
            ->wishlist()
            ->with(['category', 'sizes'])
            ->get();

        return ProductResource::collection($products);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        auth()->user()->wishlist()->syncWithoutDetaching([$request->product_id]);

        return response()->json(['message' => 'Added to wishlist']);
    }

    public function destroy(Product $product): JsonResponse
    {
        auth()->user()->wishlist()->detach($product->id);

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
