<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserPermissionsRequest extends FormRequest
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
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['nullable', Rule::in(['allow', 'deny'])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
