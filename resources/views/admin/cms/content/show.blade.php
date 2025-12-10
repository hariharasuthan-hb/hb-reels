@extends('admin.layouts.app')

@section('page-title', 'View CMS Content')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Content Details</h1>
        <div class="flex space-x-2">
            <a href="{{ route('admin.cms.content.edit', $content->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Edit
            </a>
            <a href="{{ route('admin.cms.content.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                Back to Content
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $content->title }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Key: <span class="font-mono">{{ $content->key }}</span></p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $content->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $content->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ ucfirst($content->type) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Content Body --}}
        <div class="px-6 py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Left Column --}}
                <div class="space-y-6">
                    {{-- Image --}}
                    @if($content->image)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image</label>
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($content->image) }}" alt="{{ $content->title }}" class="w-full h-64 object-cover rounded-lg border border-gray-300">
                    </div>
                    @endif

                    {{-- Description --}}
                    @if($content->description)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <p class="text-gray-900">{{ $content->description }}</p>
                    </div>
                    @endif

                    {{-- Link --}}
                    @if($content->link)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Link</label>
                        <a href="{{ $content->link }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                            {{ $content->link_text ?? $content->link }}
                            <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Right Column --}}
                <div class="space-y-6">
                    {{-- Content --}}
                    @if($content->content)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <div class="prose max-w-none text-gray-900">
                            {!! render_content($content->content) !!}
                        </div>
                    </div>
                    @endif

                    {{-- Metadata --}}
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Order:</span>
                            <span class="text-sm text-gray-900">{{ $content->order }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Type:</span>
                            <span class="text-sm text-gray-900">{{ ucfirst($content->type) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Created:</span>
                            <span class="text-sm text-gray-900">{{ $content->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        @if($content->created_by && $content->creator)
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Created By:</span>
                            <span class="text-sm text-gray-900">{{ $content->creator->name }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Updated:</span>
                            <span class="text-sm text-gray-900">{{ $content->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                        @if($content->updated_by && $content->updater)
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700">Updated By:</span>
                            <span class="text-sm text-gray-900">{{ $content->updater->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
            <a href="{{ route('admin.cms.content.edit', $content->id) }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit
            </a>
            <form action="{{ route('admin.cms.content.destroy', $content->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        onclick="return confirm('Are you sure you want to delete this content?')">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

