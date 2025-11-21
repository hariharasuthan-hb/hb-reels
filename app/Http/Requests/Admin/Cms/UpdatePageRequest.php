<?php

namespace App\Http\Requests\Admin\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
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
        $pageId = $this->route('page');

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cms_pages', 'slug')->ignore($pageId),
            ],
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ];
    }
}
