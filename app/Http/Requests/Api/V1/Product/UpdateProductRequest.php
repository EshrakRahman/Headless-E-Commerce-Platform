<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string',
            'slug' => [
                'sometimes',
                'string',
                Rule::unique('products')->ignore($this->product)->whereNull('deleted_at'),
            ],
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'compare_price' => 'nullable|numeric|min:0',
            'quantity' => 'sometimes|numeric',
            'is_featured' => 'sometimes|boolean',
            'image' => 'sometimes|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'sizes' => 'nullable|array',
            'sizes.*.size_id' => 'required|exists:sizes,id',
            'sizes.*.additional_price' => 'nullable|numeric|min:0',
            'sizes.*.stock' => 'nullable|integer|min:0',
        ];
    }
}
