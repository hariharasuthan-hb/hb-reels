@extends('frontend.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-12">
    <article class="max-w-4xl mx-auto">
        @if($page->featured_image)
            <div class="mb-8">
                <img src="{{ asset('storage/' . $page->featured_image) }}" 
                     alt="{{ $page->title }}" 
                     class="w-full h-auto rounded-lg shadow-lg">
            </div>
        @endif

        <header class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $page->title }}</h1>
            @if($page->excerpt)
                <p class="text-xl text-gray-600">{{ $page->excerpt }}</p>
            @endif
        </header>

        <div class="prose prose-lg max-w-none">
            {!! nl2br(e($page->content)) !!}
        </div>

        <div class="mt-8 pt-8 border-t border-gray-200">
            <a href="{{ route('frontend.home') }}" 
               class="text-blue-600 hover:text-blue-800 transition">
                ← Back to Home
            </a>
        </div>
    </article>
</div>
@endsection

