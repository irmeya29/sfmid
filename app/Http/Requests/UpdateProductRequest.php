<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use App\Enums\ProductStockKind;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('products', 'code')->ignore($product)],
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
