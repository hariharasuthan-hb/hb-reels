<?php

namespace App\Http\Requests\Admin\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cms_contents', 'key')->whereNull('deleted_at'),
            ],
            'type' => 'required|string|max:100',
            'content' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'title_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'description_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'content_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv|max:51200',
            'video_is_background' => 'nullable|boolean',
            'link' => 'nullable|url|max:500',
            'link_text' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
