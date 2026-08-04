<?php

namespace App\Http\Requests;

use App\Models\StockSite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('stock_site_id')) {
            $this->merge([
                'stock_site_id' => StockSite::query()
                    ->where('is_active', true)
                    ->where('can_sell', true)
                    ->orderByDesc('is_default')
                    ->value('id'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'client_delivery_site_id' => [
                'nullable',
                'integer',
                Rule::exists('client_delivery_sites', 'id')
                    ->where('client_id', $this->integer('client_id')),
            ],
            'stock_site_id' => [
                'required',
                'integer',
                Rule::exists('stock_sites', 'id')->where('is_active', true)->where('can_sell', true),
            ],
            'planned_delivery_date' => ['nullable', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['nullable', Rule::in(['product', 'service'])],
            'items.*.product_id' => ['required_if:items.*.item_type,product', 'nullable', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required_if:items.*.item_type,service', 'nullable', 'string', 'max:255'],
            'items.*.unit' => ['required_if:items.*.item_type,service', 'nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.delivered_quantity' => ['nullable', 'numeric', 'min:0.001', 'lte:items.*.quantity'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
