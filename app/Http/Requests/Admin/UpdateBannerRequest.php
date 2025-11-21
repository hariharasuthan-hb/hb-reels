<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit banners');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'link' => 'nullable|url|max:255',
            'link_text' => 'nullable|string|max:50',
            'overlay_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg, webp.',
            'image.max' => 'The image may not be greater than 5MB.',
            'link.url' => 'The link must be a valid URL.',
            'overlay_color.regex' => 'The overlay color must be a valid hex color code (e.g., #FF0000).',
            'overlay_opacity.min' => 'The overlay opacity must be between 0 and 1.',
            'overlay_opacity.max' => 'The overlay opacity must be between 0 and 1.',
        ];
    }
}

