<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'expense_date' => ['required', 'date'],
            'beneficiary' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
