<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\StoreProductRequest;
use App\Http\Requests\Api\V1\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ProductController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $products = Product::with('category')->get();
        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $request->validated();
        $product['slug'] = $product['slug'] ?? Str::slug($product['name']);
        $product = Product::create($product);

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        $product->load('category');

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }
}
