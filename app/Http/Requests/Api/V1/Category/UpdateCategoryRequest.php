<?php

namespace App\Http\Requests\Api\V1\Category;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
            'name' => 'sometimes|string',
            'slug' => [
                'sometimes',
                'string',
                Rule::unique('categories')->WhereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean|sometimes',
        ];
    }
}
