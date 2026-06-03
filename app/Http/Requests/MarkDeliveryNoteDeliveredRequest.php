<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkDeliveryNoteDeliveredRequest extends FormRequest
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
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['nullable', 'string', 'max:50'],
            'delivered_at' => ['required', 'date'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
