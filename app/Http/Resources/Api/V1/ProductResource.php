<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->formatPrice($this->price),
            'compare_price' => $this->formatPrice($this->compare_price),
            'sale_price' => $this->sale_price !== null ? $this->formatPrice($this->sale_price) : null,
            'has_discount' => $this->sale_price !== null && $this->sale_price < (float) $this->price,
            'quantity' => (int) $this->quantity,
            'image' => $this->image
                ? \Storage::disk('s3')->url('products/'.$this->image)
                : null,
            'sizes' => $this->whenLoaded('sizes', fn () => $this->sizes->map(fn ($size) => [
                'id' => $size->id,
                'name' => $size->name,
                'additional_price' => $this->formatPrice($size->pivot->additional_price),
                'stock' => (int) $size->pivot->stock,
            ])),
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function formatPrice(?float $value): ?float
    {
        return $value !== null ? (float) number_format($value, 2, '.', '') : null;
    }
}
