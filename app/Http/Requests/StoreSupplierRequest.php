<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'ifu' => ['nullable', 'string', 'max:100'],
            'rccm' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')],
        ];
    }
}
