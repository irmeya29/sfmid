<?php

namespace App\Http\Requests;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:50', 'unique:clients,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ClientType::class)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'ifu' => ['nullable', 'string', 'max:100'],
            'rccm' => ['nullable', 'string', 'max:100'],
            'payment_delay_days' => ['required', 'integer', 'min:0', 'max:365'],
            'commercial_terms' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
        ];
    }
}
