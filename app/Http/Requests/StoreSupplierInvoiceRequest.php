<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')],
            'supplier_purchase_order_id' => ['nullable', Rule::exists('supplier_purchase_orders', 'id')],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'total' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
