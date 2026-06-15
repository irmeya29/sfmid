<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use App\Models\StockSite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
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
                    ->where('can_store', true)
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'stock_site_id' => [
                'required',
                'integer',
                Rule::exists('stock_sites', 'id')->where('is_active', true)->where('can_store', true),
            ],
            'type' => ['required', Rule::in(StockMovementType::values())],
            'stock_column' => ['required', Rule::in(['physical_stock', 'tool_stock'])],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('type'), [
                    StockMovementType::PositiveAdjustment->value,
                    StockMovementType::NegativeAdjustment->value,
                    StockMovementType::LossOrDamage->value,
                    StockMovementType::InternalUse->value,
                    StockMovementType::DeliveryExit->value,
                ], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
