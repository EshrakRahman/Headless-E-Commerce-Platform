<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\Attributes\StopOnFirstFailure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[StopOnFirstFailure]
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string',
            'slug' => [
                'required',
                'string',
                Rule::unique('products')->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean|required',
            'sizes' => 'nullable|array',
            'sizes.*.size_id' => 'required|exists:sizes,id',
            'sizes.*.additional_price' => 'nullable|numeric|min:0',
            'sizes.*.stock' => 'nullable|integer|min:0',
        ];
    }
}
