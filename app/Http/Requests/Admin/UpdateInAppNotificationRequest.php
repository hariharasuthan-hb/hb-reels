<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInAppNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'audience_type' => ['required', 'in:all,trainer,member,user'],
            'target_user_id' => ['nullable', 'exists:users,id', 'required_if:audience_type,user'],
            'status' => ['required', 'in:draft,scheduled,published,archived'],
            'scheduled_for' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'requires_acknowledgement' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_acknowledgement' => $this->boolean('requires_acknowledgement'),
        ]);
    }
}

