@extends('admin.layouts.app')

@section('page-title', 'Create CMS Content')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Create New Content</h1>
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

    <form action="{{ route('admin.cms.content.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        @csrf

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
                           value="{{ old('title') }}" 
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
                           value="{{ old('key') }}" 
                           required
                           placeholder="unique-content-key"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono">
                    <p class="mt-1 text-sm text-gray-500">Unique identifier (e.g., hero-banner, about-section)</p>
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
                        <option value="hero" {{ old('type') == 'hero' ? 'selected' : '' }}>Hero</option>
                        <option value="about" {{ old('type') == 'about' ? 'selected' : '' }}>About</option>
                        <option value="services" {{ old('type') == 'services' ? 'selected' : '' }}>Services</option>
                        <option value="testimonials" {{ old('type') == 'testimonials' ? 'selected' : '' }}>Testimonials</option>
                        <option value="features" {{ old('type') == 'features' ? 'selected' : '' }}>Features</option>
                        <option value="cta" {{ old('type') == 'cta' ? 'selected' : '' }}>Call to Action</option>
                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other</option>
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
                           value="{{ old('order', 0) }}" 
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Image --}}
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Image
                    </label>
                    <input type="file" 
                           name="image" 
                           id="image" 
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, JPG, GIF, WEBP</p>
                </div>

                {{-- Background Image --}}
                <div>
                    <label for="background_image" class="block text-sm font-medium text-gray-700 mb-2">
                        Background Image
                    </label>
                    <input type="file" 
                           name="background_image" 
                           id="background_image" 
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, JPG, GIF, WEBP. Used as background for content sections.</p>
                </div>

                {{-- Video (Testimonials, etc.) --}}
                <div>
                    <label for="video" class="block text-sm font-medium text-gray-700 mb-2">
                        Video (optional)
                    </label>
                    <input type="file" 
                           name="video" 
                           id="video" 
                           accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Max size: 50MB. Formats: MP4, MOV, AVI, WMV.</p>
                </div>

                {{-- Link --}}
                <div>
                    <label for="link" class="block text-sm font-medium text-gray-700 mb-2">
                        Link URL
                    </label>
                    <input type="url" 
                           name="link" 
                           id="link" 
                           value="{{ old('link') }}" 
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
                           value="{{ old('link_text') }}" 
                           placeholder="Learn More"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Status --}}
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           id="is_active" 
                           value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active
                    </label>
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
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Brief description (max 1000 characters)</p>
                </div>

                {{-- Content --}}
                <div>
                    @include('admin.components.rich-text-editor', [
                        'name' => 'content',
                        'label' => 'Content',
                        'value' => old('content'),
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
                Create Content
            </button>
        </div>
    </form>
</div>
@endsection

