<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit payment settings');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'enable_stripe' => ['nullable', 'boolean'],
            'stripe_publishable_key' => [
                'nullable',
                'string',
                'required_if:enable_stripe,1',
            ],
            'stripe_secret_key' => [
                'nullable',
                'string',
                'required_if:enable_stripe,1',
            ],
            'enable_razorpay' => ['nullable', 'boolean'],
            'razorpay_key_id' => [
                'nullable',
                'string',
                'required_if:enable_razorpay,1',
            ],
            'razorpay_key_secret' => [
                'nullable',
                'string',
                'required_if:enable_razorpay,1',
            ],
            'enable_gpay' => ['nullable', 'boolean'],
            'gpay_upi_id' => [
                'nullable',
                'string',
                'required_if:enable_gpay,1',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'stripe_publishable_key.required_if' => 'Stripe publishable key is required when Stripe is enabled.',
            'stripe_secret_key.required_if' => 'Stripe secret key is required when Stripe is enabled.',
            'razorpay_key_id.required_if' => 'Razorpay key ID is required when Razorpay is enabled.',
            'razorpay_key_secret.required_if' => 'Razorpay key secret is required when Razorpay is enabled.',
            'gpay_upi_id.required_if' => 'Google Pay UPI ID is required when Google Pay is enabled.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'enable_stripe' => $this->has('enable_stripe') ? (bool) $this->enable_stripe : false,
            'enable_razorpay' => $this->has('enable_razorpay') ? (bool) $this->enable_razorpay : false,
            'enable_gpay' => $this->has('enable_gpay') ? (bool) $this->enable_gpay : false,
        ]);
    }
}
