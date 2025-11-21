<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'spent_at' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'reference_document' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png,gif,webp', 'max:5120'],
            'remove_reference_document' => ['nullable', 'boolean'],
        ];
    }
}


