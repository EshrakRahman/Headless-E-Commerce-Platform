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

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     *
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->whenLoaded('category', fn() => $this->category?->name),
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'quantity' => (int)$this->quantity,
            'image' => $this->image ? asset('storage/products/' . $this->image) : null ,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
