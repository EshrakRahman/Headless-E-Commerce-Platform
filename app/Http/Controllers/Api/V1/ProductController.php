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
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['category', 'sizes']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->sort === 'latest') {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->filled('limit')) {
            $query->take($request->integer('limit'));
        }

        return ProductResource::collection($query->get());
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $validated = $request->validated();
        $sizes = $validated['sizes'] ?? null;
        unset($validated['sizes']);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $product = Product::create($validated);

        if ($sizes) {
            $this->syncSizes($product, $sizes);
        }

        $product->load('category');

        return new ProductResource($product);
    }

    public function showBySlug(string $slug): ProductResource
    {
        $product = Product::where('slug', $slug)->with(['category', 'sizes'])->firstOrFail();

        return new ProductResource($product);
    }

    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'sizes']);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $validated = $request->validated();
        $sizes = $validated['sizes'] ?? null;
        unset($validated['sizes']);

        $product->update($validated);

        if ($sizes !== null) {
            $this->syncSizes($product, $sizes);
        }

        $product->load('category');

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->noContent();
    }

    private function syncSizes(Product $product, array $sizes): void
    {
        $sizeData = collect($sizes)->mapWithKeys(fn (array $item) => [
            $item['size_id'] => [
                'additional_price' => $item['additional_price'] ?? 0,
                'stock' => $item['stock'] ?? null,
            ],
        ]);

        $product->sizes()->sync($sizeData);
    }
}
