@extends('admin.layouts.app')

@section('page-title', 'Edit Banner')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Edit Banner</h1>
        </div>
        <a href="{{ route('admin.banners.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">
            Back to Banners
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6">
            <ul class="list-disc list-inside text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-8">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            {{-- Image Section --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Banner Image
                </h3>
                <div>
                    @if($banner->image)
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Current Image</label>
                            <img src="{{ Storage::url($banner->image) }}" 
                                 alt="{{ $banner->title }}" 
                                 class="max-w-full h-64 object-cover rounded-lg border border-gray-300">
                        </div>
                    @endif
                    <label for="image" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ $banner->image ? 'Replace Image' : 'Upload Image' }}
                    </label>
                    <input type="file" 
                           name="image" 
                           id="image" 
                           accept="image/*"
                           onchange="previewImage(this)"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-lg p-2">
                    <p class="mt-2 text-xs text-gray-500">Leave empty to keep current image. Recommended: 1920x800px or larger, max 5MB.</p>
                    <div id="image-preview" class="mt-4 hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">New Image Preview</label>
                        <img id="preview-img" src="" alt="Preview" class="max-w-full h-64 object-cover rounded-lg border border-gray-300">
                    </div>
                </div>
            </div>

            {{-- Content Section --}}
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl border border-green-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Banner Content
                </h3>
                <div class="space-y-4">
                    @include('admin.components.form-input', [
                        'name' => 'title',
                        'label' => 'Title',
                        'value' => old('title', $banner->title),
                        'placeholder' => 'Banner Title (Optional)',
                    ])
                    
                    @include('admin.components.form-textarea', [
                        'name' => 'subtitle',
                        'label' => 'Subtitle',
                        'value' => old('subtitle', $banner->subtitle),
                        'placeholder' => 'Banner subtitle or description (Optional)',
                        'rows' => 3,
                    ])
                </div>
            </div>

            {{-- Link Section --}}
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-xl border border-purple-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Call to Action (Optional)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @include('admin.components.form-input', [
                        'name' => 'link',
                        'label' => 'Link URL',
                        'type' => 'url',
                        'value' => old('link', $banner->link),
                        'placeholder' => 'https://example.com',
                    ])
                    
                    @include('admin.components.form-input', [
                        'name' => 'link_text',
                        'label' => 'Button Text',
                        'value' => old('link_text', $banner->link_text ?? 'Learn More'),
                        'placeholder' => 'Learn More',
                    ])
                </div>
            </div>

            {{-- Display Settings --}}
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-6 rounded-xl border border-orange-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    Display Settings
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @include('admin.components.form-input', [
                        'name' => 'order',
                        'label' => 'Display Order',
                        'type' => 'number',
                        'value' => old('order', $banner->order),
                        'attributes' => ['min' => '0'],
                    ])
                    
                    <div class="md:col-span-1">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', $banner->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-semibold text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    @include('admin.components.form-input', [
                        'name' => 'overlay_color',
                        'label' => 'Overlay Color',
                        'type' => 'color',
                        'value' => old('overlay_color', $banner->overlay_color ?? '#000000'),
                    ])
                    
                    @include('admin.components.form-input', [
                        'name' => 'overlay_opacity',
                        'label' => 'Overlay Opacity',
                        'type' => 'number',
                        'value' => old('overlay_opacity', $banner->overlay_opacity ?? 0.5),
                        'attributes' => ['min' => '0', 'max' => '1', 'step' => '0.1'],
                    ])
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="mt-8 flex justify-end space-x-4">
            <a href="{{ route('admin.banners.index') }}" 
               class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-semibold">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-semibold shadow-lg hover:shadow-xl">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Update Banner
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
    }
}
</script>
@endsection

