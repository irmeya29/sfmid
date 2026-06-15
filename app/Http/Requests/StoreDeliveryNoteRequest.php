<?php

namespace App\Http\Requests;

use App\Models\StockSite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryNoteRequest extends FormRequest
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
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.delivered_quantity' => ['nullable', 'numeric', 'min:0.001', 'lte:items.*.quantity'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
