<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            'source_type' => ['required', Rule::in(['delivery_note', 'proforma', 'direct'])],
            'delivery_note_id' => ['required_if:source_type,delivery_note', 'nullable', 'integer', 'exists:delivery_notes,id'],
            'proforma_id' => ['required_if:source_type,proforma', 'nullable', 'integer', 'exists:proformas,id'],

            'client_id' => ['required_if:source_type,direct', 'nullable', 'integer', 'exists:clients,id'],
            'issue_date' => ['required_if:source_type,direct', 'nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'subject' => ['required_if:source_type,direct', 'nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'delivery_delay' => ['nullable', 'string', 'max:255'],
            'apply_tax' => ['nullable', 'boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required_if:source_type,direct', 'array', 'min:1'],
            'items.*.product_id' => ['required_if:source_type,direct', 'nullable', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required_if:source_type,direct', 'nullable', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
