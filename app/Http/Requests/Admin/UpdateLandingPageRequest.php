<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit landing page');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'hero_background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'welcome_title' => 'required|string|max:255',
            'welcome_subtitle' => 'nullable|string|max:500',
            'about_title' => 'required|string|max:255',
            'about_description' => 'nullable|string',
            'about_features' => 'nullable|array',
            'about_features.*.icon' => 'nullable|string|max:50',
            'about_features.*.title' => 'nullable|string|max:255',
            'about_features.*.description' => 'nullable|string|max:500',
            'services_title' => 'required|string|max:255',
            'services_description' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*.title' => 'nullable|string|max:255',
            'services.*.description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'welcome_title.required' => 'The welcome title field is required.',
            'about_title.required' => 'The about title field is required.',
            'services_title.required' => 'The services title field is required.',
            'logo.image' => 'The file must be an image.',
            'logo.mimes' => 'The logo must be a file of type: jpeg, png, jpg, gif, svg, webp.',
            'logo.max' => 'The logo may not be greater than 2MB.',
            'hero_background_image.image' => 'The file must be an image.',
            'hero_background_image.mimes' => 'The background image must be a file of type: jpeg, png, jpg, gif, svg, webp.',
            'hero_background_image.max' => 'The background image may not be greater than 5MB.',
        ];
    }
}

