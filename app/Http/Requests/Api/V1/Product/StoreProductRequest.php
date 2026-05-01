<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\Attributes\StopOnFirstFailure;
use Illuminate\Foundation\Http\FormRequest;

#[StopOnFirstFailure]
#[FailOnUnknownFields]
class StoreProductRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string',
            'slug' => 'required|string|unique:categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'price' => 'required|numeric',
            'quantity' => 'nullable|numeric',
            'is_featured' => 'boolean|required',
        ];
    }
}
