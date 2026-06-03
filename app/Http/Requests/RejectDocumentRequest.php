<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectDocumentRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Le motif de rejet est obligatoire.',
            'reason.min' => 'Le motif de rejet doit contenir au moins 5 caractères.',
        ];
    }
}
