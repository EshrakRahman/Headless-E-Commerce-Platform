<?php

namespace App\Http\Requests\Api\V1\Product;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @mixin Product
 * @property mixed $product
 */
class UpdateProductRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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
            'quantity' => 'sometimes|numeric',
            'is_featured' => 'sometimes|boolean',
            'image' => 'sometimes|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
        ];
    }
}
