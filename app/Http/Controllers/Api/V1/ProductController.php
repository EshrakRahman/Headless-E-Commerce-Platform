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
     * List products with optional search, category filter, featured filter, and sorting.
     *
     * @tags Products
     *
     * @unauthenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['category', 'brand', 'sizes', 'discounts']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $request->brand));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('on_sale')) {
            $query->where(function ($q) {
                $q->whereHas('discounts', fn ($subQuery) => $subQuery->active())
                    ->orWhereColumn('compare_price', '>', 'price');
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        if ($request->filled('size')) {
            $query->whereHas('sizes', fn ($q) => $q->where('name', $request->size));
        }

        if ($request->sort === 'latest') {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->filled('limit')) {
            $query->take($request->integer('limit'));

            return ProductResource::collection($query->get());
        }

        return ProductResource::collection($query->cursorPaginate($request->integer('per_page', 20)));
    }

    /**
     * Create a new product.
     *
     * @tags Products
     *
     * @unauthenticated
     */
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

    /**
     * Get a product by its slug.
     *
     * @tags Products
     *
     * @unauthenticated
     */
    public function showBySlug(string $slug): ProductResource
    {
        $product = Product::where('slug', $slug)->with(['category', 'brand', 'sizes', 'discounts'])->firstOrFail();

        return new ProductResource($product);
    }

    /**
     * Get a product by its ID.
     *
     * @tags Products
     *
     * @unauthenticated
     */
    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'brand', 'sizes', 'discounts']);

        return new ProductResource($product);
    }

    /**
     * Update an existing product.
     *
     * @tags Products
     *
     * @unauthenticated
     */
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

    /**
     * Soft-delete a product.
     *
     * @tags Products
     *
     * @unauthenticated
     */
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
