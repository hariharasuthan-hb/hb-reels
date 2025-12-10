@extends('admin.layouts.app')

@section('page-title', 'View CMS Page')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">{{ $page->title }}</h1>
        <div class="flex space-x-3">
            <a href="{{ route('admin.cms.pages.edit', $page->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Edit Page
            </a>
            <a href="{{ route('admin.cms.pages.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                Back to Pages
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Featured Image --}}
            @if($page->featured_image)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Featured Image</h2>
                    <img src="{{ asset('storage/' . $page->featured_image) }}" 
                         alt="{{ $page->title }}" 
                         class="w-full h-auto rounded-lg">
                </div>
            @endif

            {{-- Content --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Content</h2>
                @if($page->content)
                    <div class="prose max-w-none">
                        {!! render_content($page->content) !!}
                    </div>
                @else
                    <p class="text-gray-500 italic">No content available.</p>
                @endif
            </div>

            {{-- Excerpt --}}
            @if($page->excerpt)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Excerpt</h2>
                    <p class="text-gray-700">{{ $page->excerpt }}</p>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Page Information --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Page Information</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Slug</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $page->slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $page->category ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $page->order }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $page->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $page->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Featured</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $page->is_featured ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $page->is_featured ? 'Yes' : 'No' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Timestamps --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Timestamps</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $page->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Updated At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $page->updated_at->format('M d, Y H:i') }}</dd>
                    </div>
                    @if($page->creator)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $page->creator->name }}</dd>
                        </div>
                    @endif
                    @if($page->updater)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Updated By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $page->updater->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- SEO Information --}}
            @if($page->meta_title || $page->meta_description || $page->meta_keywords)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">SEO Information</h2>
                    <dl class="space-y-3">
                        @if($page->meta_title)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Meta Title</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $page->meta_title }}</dd>
                            </div>
                        @endif
                        @if($page->meta_description)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Meta Description</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $page->meta_description }}</dd>
                            </div>
                        @endif
                        @if($page->meta_keywords)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Meta Keywords</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $page->meta_keywords }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            {{-- Actions --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.cms.pages.edit', $page->id) }}" 
                       class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Edit Page
                    </a>
                    <form action="{{ route('admin.cms.pages.destroy', $page->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="block w-full text-center bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
                                onclick="return confirm('Are you sure you want to delete this page?')">
                            Delete Page
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

