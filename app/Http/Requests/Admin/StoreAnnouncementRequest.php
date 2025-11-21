<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'audience_type' => ['required', 'in:all,trainer,member'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status', 'draft'),
            'audience_type' => $this->input('audience_type', 'all'),
        ]);
    }
}

