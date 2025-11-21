<?php

namespace App\Http\Requests\Admin;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit subscription plans');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'plan_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'duration_type' => ['required', 'string', Rule::in(SubscriptionPlan::getValidDurationTypes())],
            'duration' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_name.required' => 'The plan name field is required.',
            'duration_type.required' => 'The duration type field is required.',
            'duration_type.in' => 'The duration type must be one of: ' . implode(', ', SubscriptionPlan::getValidDurationTypes()) . '.',
            'duration.required' => 'The duration field is required.',
            'duration.min' => 'The duration must be at least 1.',
            'price.required' => 'The price field is required.',
            'price.numeric' => 'The price must be a valid number.',
            'price.min' => 'The price must be at least 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? (bool) $this->is_active : false,
        ]);
    }
}

