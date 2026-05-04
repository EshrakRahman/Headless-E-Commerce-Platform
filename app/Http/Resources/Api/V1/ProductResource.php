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
            'price' => (float) $this->price,
            'compare_price' => (float) $this->compare_price,
            'quantity' => (int) $this->quantity,
            'image' => $this->image
                ? (str_starts_with($this->image, 'http') ? $this->image : asset('storage/products/'.$this->image))
                : null,
            'sizes' => $this->whenLoaded('sizes', fn () => $this->sizes->map(fn ($size) => [
                'id' => $size->id,
                'name' => $size->name,
                'additional_price' => (float) $size->pivot->additional_price,
                'stock' => (int) $size->pivot->stock,
            ])),
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
