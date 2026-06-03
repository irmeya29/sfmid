<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use App\Enums\ProductStockKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'code' => ['nullable', 'string', 'max:50', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'internal_reference' => ['nullable', 'string', 'max:255'],
            'supplier_reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', 'string', 'max:50'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'physical_stock' => ['required', 'numeric', 'min:0'],
            'tool_stock' => ['required', 'numeric', 'min:0'],
            'alert_threshold' => ['required', 'numeric', 'min:0'],
            'stock_kind' => ['required', Rule::enum(ProductStockKind::class)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }
}
