<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route('expense_category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($category)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('expense_categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_sensitive' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
