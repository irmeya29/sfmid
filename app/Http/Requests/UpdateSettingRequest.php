<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
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
            'company.name' => ['required', 'string', 'max:255'],
            'company.full_name' => ['nullable', 'string', 'max:255'],
            'company.phone' => ['nullable', 'string', 'max:50'],
            'company.email' => ['nullable', 'email', 'max:255'],
            'company.address' => ['nullable', 'string', 'max:1000'],
            'company.ifu' => ['nullable', 'string', 'max:100'],
            'company.rccm' => ['nullable', 'string', 'max:100'],
            'company.logo' => ['nullable', 'image', 'max:2048'],
            'sales.currency' => ['required', 'string', 'max:20'],
            'sales.default_payment_delay_days' => ['required', 'integer', 'min:0', 'max:365'],
            'sales.default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock.reserve_on_proforma' => ['nullable', 'boolean'],
            'stock.allow_negative_stock' => ['nullable', 'boolean'],
            'stock.low_stock_alert_enabled' => ['nullable', 'boolean'],
            'pdf.footer_note' => ['nullable', 'string', 'max:1000'],
            'pdf.signature_left' => ['nullable', 'string', 'max:100'],
            'pdf.signature_right' => ['nullable', 'string', 'max:100'],
            'pdf.header_image' => ['nullable', 'image', 'max:3072'],
            'pdf.footer_image' => ['nullable', 'image', 'max:3072'],
            'sequences' => ['nullable', 'array'],
            'sequences.*.prefix' => ['required', 'string', 'max:30'],
            'sequences.*.next_number' => ['required', 'integer', 'min:1'],
            'sequences.*.padding' => ['required', 'integer', 'min:2', 'max:10'],
            'sequences.*.reset_period' => ['nullable', Rule::in(['daily', 'monthly', 'yearly'])],
        ];
    }
}
