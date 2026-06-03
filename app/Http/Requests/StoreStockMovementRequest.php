<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
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
