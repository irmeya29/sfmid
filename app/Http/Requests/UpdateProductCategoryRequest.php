<?php

namespace App\Http\Requests;

use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ProductCategory $category */
        $category = $this->route('product_category');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id', Rule::notIn([$category?->id])],
            'name' => ['required', 'string', 'max:255', Rule::unique('product_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
