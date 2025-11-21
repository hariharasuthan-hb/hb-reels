<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit site settings');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'site_title' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'contact_email' => 'nullable|email|max:255',
            'contact_mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'footer_partner' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'site_title.required' => 'The site title field is required.',
            'logo.image' => 'The file must be an image.',
            'logo.mimes' => 'The logo must be a file of type: jpeg, png, jpg, gif, svg, webp.',
            'logo.max' => 'The logo may not be greater than 2MB.',
            'contact_email.email' => 'Please enter a valid email address.',
            'facebook_url.url' => 'The Facebook URL must be a valid URL.',
            'twitter_url.url' => 'The Twitter URL must be a valid URL.',
            'instagram_url.url' => 'The Instagram URL must be a valid URL.',
            'linkedin_url.url' => 'The LinkedIn URL must be a valid URL.',
        ];
    }
}

