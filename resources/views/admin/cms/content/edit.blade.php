@extends('admin.layouts.app')

@section('page-title', 'Edit CMS Content')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Edit Content</h1>
        <a href="{{ route('admin.cms.content.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Back to Content
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.cms.content.update', $content->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Left Column --}}
            <div class="space-y-6">
                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           value="{{ old('title', $content->title) }}" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Key --}}
                <div>
                    <label for="key" class="block text-sm font-medium text-gray-700 mb-2">
                        Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="key" 
                           id="key" 
                           value="{{ old('key', $content->key) }}" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono">
                    <p class="mt-1 text-sm text-gray-500">Unique identifier</p>
                </div>

                {{-- Type --}}
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <select name="type" 
                            id="type" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Type</option>
                        <option value="hero" {{ old('type', $content->type) == 'hero' ? 'selected' : '' }}>Hero</option>
                        <option value="about" {{ old('type', $content->type) == 'about' ? 'selected' : '' }}>About</option>
                        <option value="services" {{ old('type', $content->type) == 'services' ? 'selected' : '' }}>Services</option>
                        <option value="testimonials" {{ old('type', $content->type) == 'testimonials' ? 'selected' : '' }}>Testimonials</option>
                        <option value="features" {{ old('type', $content->type) == 'features' ? 'selected' : '' }}>Features</option>
                        <option value="cta" {{ old('type', $content->type) == 'cta' ? 'selected' : '' }}>Call to Action</option>
                        <option value="other" {{ old('type', $content->type) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                {{-- Order --}}
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Order
                    </label>
                    <input type="number" 
                           name="order" 
                           id="order" 
                           value="{{ old('order', $content->order) }}" 
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Current Image --}}
                @if($content->image)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Image
                    </label>
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($content->image) }}" alt="{{ $content->title }}" class="h-32 w-full object-cover rounded-lg border border-gray-300">
                    <div class="mt-2 flex items-center">
                        <input type="checkbox" name="remove_image" id="remove_image" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="remove_image" class="ml-2 text-sm text-red-600 font-medium">Remove current image</label>
                    </div>
                </div>
                @endif

                {{-- Image --}}
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $content->image ? 'Replace Image' : 'Image' }}
                    </label>
                    <input type="file" 
                           name="image" 
                           id="image" 
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, JPG, GIF, WEBP</p>
                </div>

                {{-- Current Background Image --}}
                @if($content->background_image)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Background Image
                    </label>
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($content->background_image) }}" alt="{{ $content->title }} Background" class="h-32 w-full object-cover rounded-lg border border-gray-300">
                    <div class="mt-2 flex items-center">
                        <input type="checkbox" name="remove_background_image" id="remove_background_image" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="remove_background_image" class="ml-2 text-sm text-red-600 font-medium">Remove current background image</label>
                    </div>
                </div>
                @endif

                {{-- Background Image --}}
                <div>
                    <label for="background_image" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $content->background_image ? 'Replace Background Image' : 'Background Image' }}
                    </label>
                    <input type="file" 
                           name="background_image" 
                           id="background_image" 
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, JPG, GIF, WEBP. Used as background for content sections.</p>
                </div>

                {{-- Current Video --}}
                @if($content->video_path)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Video
                    </label>
                    <video controls class="w-full rounded-lg border border-gray-300 mb-2">
                        <source src="{{ \Illuminate\Support\Facades\Storage::url($content->video_path) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="mt-2 flex items-center">
                        <input type="checkbox" name="remove_video" id="remove_video" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="remove_video" class="ml-2 text-sm text-red-600 font-medium">Remove current video</label>
                    </div>
                </div>
                @endif

                {{-- Video Upload --}}
                <div>
                    <label for="video" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $content->video_path ? 'Replace Video' : 'Video (optional)' }}
                    </label>
                    <input type="file"
                           name="video"
                           id="video"
                           accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <div class="mt-2 flex items-center">
                        <input type="checkbox"
                               name="video_is_background"
                               id="video_is_background"
                               value="1"
                               {{ old('video_is_background', $content->video_is_background ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="video_is_background" class="ml-2 text-sm text-gray-700 font-medium">
                            Set as background video for homepage section
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Max size: 50MB. Formats: MP4, MOV, AVI, WMV. When checked, video will be used as background instead of content.</p>
                </div>

                {{-- Link --}}
                <div>
                    <label for="link" class="block text-sm font-medium text-gray-700 mb-2">
                        Link URL
                    </label>
                    <input type="url" 
                           name="link" 
                           id="link" 
                           value="{{ old('link', $content->link) }}" 
                           placeholder="https://example.com"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Link Text --}}
                <div>
                    <label for="link_text" class="block text-sm font-medium text-gray-700 mb-2">
                        Link Text
                    </label>
                    <input type="text" 
                           name="link_text" 
                           id="link_text" 
                           value="{{ old('link_text', $content->link_text) }}" 
                           placeholder="Learn More"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Status --}}
                <div class="flex items-center">
                    <input type="checkbox"
                           name="is_active"
                           id="is_active"
                           value="1"
                           {{ old('is_active', $content->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active
                    </label>
                </div>

                {{-- Text Colors --}}
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Text Colors (Optional)</h4>
                    <div class="space-y-3">
                        {{-- Title Color --}}
                        <div>
                            <label for="title_color" class="block text-sm text-gray-600 mb-1">
                                Title Color
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="color"
                                       name="title_color"
                                       id="title_color"
                                       value="{{ old('title_color', $content->title_color ?? '#000000') }}"
                                       class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                <input type="text"
                                       value="{{ old('title_color', $content->title_color ?? '#000000') }}"
                                       readonly
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono">
                                <button type="button" onclick="document.getElementById('title_color').value='#000000'; document.querySelector('[name=title_color]').nextElementSibling.value='#000000';" class="text-xs text-gray-500 hover:text-gray-700">Reset</button>
                            </div>
                        </div>

                        {{-- Description Color --}}
                        <div>
                            <label for="description_color" class="block text-sm text-gray-600 mb-1">
                                Description Color
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="color"
                                       name="description_color"
                                       id="description_color"
                                       value="{{ old('description_color', $content->description_color ?? '#666666') }}"
                                       class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                <input type="text"
                                       value="{{ old('description_color', $content->description_color ?? '#666666') }}"
                                       readonly
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono">
                                <button type="button" onclick="document.getElementById('description_color').value='#666666'; document.querySelector('[name=description_color]').nextElementSibling.value='#666666';" class="text-xs text-gray-500 hover:text-gray-700">Reset</button>
                            </div>
                        </div>

                        {{-- Content Color --}}
                        <div>
                            <label for="content_color" class="block text-sm text-gray-600 mb-1">
                                Content Color
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="color"
                                       name="content_color"
                                       id="content_color"
                                       value="{{ old('content_color', $content->content_color ?? '#333333') }}"
                                       class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                <input type="text"
                                       value="{{ old('content_color', $content->content_color ?? '#333333') }}"
                                       readonly
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono">
                                <button type="button" onclick="document.getElementById('content_color').value='#333333'; document.querySelector('[name=content_color]').nextElementSibling.value='#333333';" class="text-xs text-gray-500 hover:text-gray-700">Reset</button>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Customize text colors for this content section. Leave empty to use default colors.</p>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="4"
                              maxlength="1000"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $content->description) }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Brief description (max 1000 characters)</p>
                </div>

                {{-- Content --}}
                <div>
                    @include('admin.components.rich-text-editor', [
                        'name' => 'content',
                        'label' => 'Content',
                        'value' => old('content', $content->content),
                        'height' => 500,
                        'toolbar' => 'full',
                        'help' => 'Main content text (supports HTML formatting)',
                    ])
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="mt-8 flex justify-end space-x-4">
            <a href="{{ route('admin.cms.content.index') }}" 
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Update Content
            </button>
        </div>
    </form>

    {{-- Color Picker JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sync color pickers with text inputs
            const colorInputs = ['title_color', 'description_color', 'content_color'];

            colorInputs.forEach(function(colorName) {
                const colorPicker = document.getElementById(colorName);
                const textInput = colorPicker.nextElementSibling;

                if (colorPicker && textInput) {
                    // Update text input when color picker changes
                    colorPicker.addEventListener('input', function() {
                        textInput.value = this.value;
                    });

                    // Update color picker when text input changes (for form reset)
                    textInput.addEventListener('input', function() {
                        if (this.value.match(/^#[a-fA-F0-9]{6}$/)) {
                            colorPicker.value = this.value;
                        }
                    });
                }
            });
        });
    </script>
</div>
@endsection

