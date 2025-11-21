@extends('admin.layouts.app')

@section('page-title', 'Edit CMS Page')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Edit Page: {{ $page->title }}</h1>
        <a href="{{ route('admin.cms.pages.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            Back to Pages
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

    <form action="{{ route('admin.cms.pages.update', $page->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
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
                           value="{{ old('title', $page->title) }}" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Slug --}}
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                        Slug
                    </label>
                    <input type="text" 
                           name="slug" 
                           id="slug" 
                           value="{{ old('slug', $page->slug) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Leave empty to auto-generate from title</p>
                </div>

                {{-- Category --}}
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        Category
                    </label>
                    <input type="text" 
                           name="category" 
                           id="category" 
                           value="{{ old('category', $page->category) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Order --}}
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Order
                    </label>
                    <input type="number" 
                           name="order" 
                           id="order" 
                           value="{{ old('order', $page->order) }}" 
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Featured Image --}}
                <div>
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-2">
                        Featured Image
                    </label>
                    @if($page->featured_image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $page->featured_image) }}" 
                                 alt="Current featured image" 
                                 class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                            <p class="text-sm text-gray-500 mt-1">Current image</p>
                        </div>
                    @endif
                    <input type="file" 
                           name="featured_image" 
                           id="featured_image" 
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, JPG, GIF, WEBP</p>
                </div>

                {{-- Status Toggles --}}
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1"
                               {{ old('is_active', $page->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Active
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_featured" 
                               id="is_featured" 
                               value="1"
                               {{ old('is_featured', $page->is_featured) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_featured" class="ml-2 block text-sm text-gray-700">
                            Featured
                        </label>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                {{-- Excerpt --}}
                <div>
                    <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">
                        Excerpt
                    </label>
                    <textarea name="excerpt" 
                              id="excerpt" 
                              rows="3"
                              maxlength="500"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('excerpt', $page->excerpt) }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Brief description (max 500 characters)</p>
                </div>

                {{-- Content --}}
                <div>
                    @include('admin.components.rich-text-editor', [
                        'name' => 'content',
                        'label' => 'Content',
                        'value' => old('content', $page->content),
                        'height' => 500,
                        'toolbar' => 'full',
                        'help' => 'Main page content (supports HTML formatting)',
                    ])
                </div>
            </div>
        </div>

        {{-- SEO Section --}}
        <div class="mt-8 pt-8 border-t border-gray-200">
            <h2 class="text-xl font-semibold mb-4">SEO Settings</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Meta Title --}}
                <div>
                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">
                        Meta Title
                    </label>
                    <input type="text" 
                           name="meta_title" 
                           id="meta_title" 
                           value="{{ old('meta_title', $page->meta_title) }}" 
                           maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Meta Keywords --}}
                <div>
                    <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                        Meta Keywords
                    </label>
                    <input type="text" 
                           name="meta_keywords" 
                           id="meta_keywords" 
                           value="{{ old('meta_keywords', $page->meta_keywords) }}" 
                           maxlength="255"
                           placeholder="keyword1, keyword2, keyword3"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Meta Description --}}
                <div class="md:col-span-2">
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                        Meta Description
                    </label>
                    <textarea name="meta_description" 
                              id="meta_description" 
                              rows="3"
                              maxlength="500"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('meta_description', $page->meta_description) }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Recommended: 150-160 characters for optimal SEO</p>
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="mt-8 flex justify-end space-x-4">
            <a href="{{ route('admin.cms.pages.index') }}" 
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Update Page
            </button>
        </div>
    </form>
</div>

<script>
    // Auto-generate slug from title
    document.getElementById('title').addEventListener('input', function() {
        const slugInput = document.getElementById('slug');
        if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
            const title = this.value;
            const slug = title.toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugInput.value = slug;
            slugInput.dataset.autoGenerated = 'true';
        }
    });

    // Reset auto-generation flag when user manually edits slug
    document.getElementById('slug').addEventListener('input', function() {
        this.dataset.autoGenerated = 'false';
    });
</script>
@endsection

