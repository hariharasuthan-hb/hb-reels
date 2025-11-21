@extends('admin.layouts.app')

@section('page-title', 'Landing Page Content')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Landing Page Content</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" 
           class="inline-flex items-center px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 bg-green-100 border-l-4 border-green-500 p-4 rounded-lg shadow-sm">
            <div class="flex">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm font-semibold text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
            <div class="flex">
                <svg class="h-5 w-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form Card --}}
    <form action="{{ route('admin.landing-page.update', $content->id) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        @csrf
        @method('PUT')
        
        <div class="p-8 space-y-8">
            {{-- Logo & Images Section --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Logo & Images
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Logo --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Logo
                        </label>
                        @if($content->logo)
                            <div class="mb-3">
                                <img src="{{ Storage::url($content->logo) }}" alt="Logo" class="h-20 object-contain">
                            </div>
                        @endif
                        <input type="file" name="logo" accept="image/*" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Recommended: PNG or SVG, max 2MB</p>
                    </div>

                    {{-- Hero Background Image --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Hero Background Image
                        </label>
                        @if($content->hero_background_image)
                            <div class="mb-3">
                                <img src="{{ Storage::url($content->hero_background_image) }}" alt="Background" class="h-32 w-full object-cover rounded-lg">
                            </div>
                        @endif
                        <input type="file" name="hero_background_image" accept="image/*" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Recommended: JPG or PNG, max 5MB</p>
                    </div>
                </div>
            </div>

            {{-- Welcome Section --}}
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl border border-green-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Welcome Section
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Welcome Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="welcome_title" value="{{ old('welcome_title', $content->welcome_title) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="Welcome to Our Gym">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Welcome Subtitle
                        </label>
                        <textarea name="welcome_subtitle" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                  placeholder="Transform your body, transform your life">{{ old('welcome_subtitle', $content->welcome_subtitle) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- About Us Section --}}
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-xl border border-purple-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    About Us Section
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            About Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="about_title" value="{{ old('about_title', $content->about_title) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               placeholder="About Us">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            About Description
                        </label>
                        <textarea name="about_description" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                  placeholder="We are dedicated to helping you achieve your fitness goals...">{{ old('about_description', $content->about_description) }}</textarea>
                    </div>

                    {{-- About Features --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            About Features
                        </label>
                        <div id="about-features-container" class="space-y-3">
                            @php
                                $features = old('about_features', $content->about_features ?? [
                                    ['icon' => '💪', 'title' => 'Expert Trainers', 'description' => 'Certified professionals to guide you'],
                                    ['icon' => '🏋️', 'title' => 'Modern Equipment', 'description' => 'Latest fitness equipment available'],
                                    ['icon' => '👥', 'title' => 'Community Support', 'description' => 'Join a supportive fitness community'],
                                ]);
                            @endphp
                            @foreach($features as $index => $feature)
                                <div class="feature-item bg-white p-4 rounded-lg border border-gray-200">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Icon</label>
                                            <input type="text" name="about_features[{{ $index }}][icon]" value="{{ $feature['icon'] ?? '' }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-lg" placeholder="💪">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Title</label>
                                            <input type="text" name="about_features[{{ $index }}][title]" value="{{ $feature['title'] ?? '' }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Feature Title">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                                            <input type="text" name="about_features[{{ $index }}][description]" value="{{ $feature['description'] ?? '' }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Feature description">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addAboutFeature()" class="mt-2 text-sm text-purple-600 hover:text-purple-800 font-medium">
                            + Add Feature
                        </button>
                    </div>
                </div>
            </div>

            {{-- Services Section --}}
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-6 rounded-xl border border-orange-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Services Section
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Services Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="services_title" value="{{ old('services_title', $content->services_title) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Our Services">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Services Description
                        </label>
                        <textarea name="services_description" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                  placeholder="Choose from our range of fitness programs...">{{ old('services_description', $content->services_description) }}</textarea>
                    </div>

                    {{-- Services List --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            Services List
                        </label>
                        <div id="services-container" class="space-y-3">
                            @php
                                $services = old('services', $content->services ?? [
                                    ['title' => 'Personal Training', 'description' => 'One-on-one training sessions with expert trainers'],
                                    ['title' => 'Group Classes', 'description' => 'Join group fitness classes for motivation'],
                                    ['title' => 'Wellness Coaching', 'description' => 'Holistic guidance for balanced habits'],
                                ]);
                            @endphp
                            @foreach($services as $index => $service)
                                <div class="service-item bg-white p-4 rounded-lg border border-gray-200">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Service Title</label>
                                            <input type="text" name="services[{{ $index }}][title]" value="{{ $service['title'] ?? '' }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Service Title">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                                            <input type="text" name="services[{{ $index }}][description]" value="{{ $service['description'] ?? '' }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Service description">
                                        </div>
                                    </div>
                                    <button type="button" onclick="removeService(this)" class="mt-2 text-xs text-red-600 hover:text-red-800">
                                        Remove
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addService()" class="mt-2 text-sm text-orange-600 hover:text-orange-800 font-medium">
                            + Add Service
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="bg-gray-50 px-8 py-6 border-t border-gray-200 flex justify-end space-x-4">
            <a href="{{ route('admin.dashboard') }}" 
               class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                      font-medium transition duration-200">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg 
                           hover:from-blue-700 hover:to-blue-800 font-medium shadow-md hover:shadow-lg 
                           transition duration-200 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Update Landing Page
            </button>
        </div>
    </form>
</div>

<script>
let featureIndex = {{ count($features ?? []) }};
let serviceIndex = {{ count($services ?? []) }};

function addAboutFeature() {
    const container = document.getElementById('about-features-container');
    const featureHtml = `
        <div class="feature-item bg-white p-4 rounded-lg border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Icon</label>
                    <input type="text" name="about_features[${featureIndex}][icon]" value=""
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-lg" placeholder="💪">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="about_features[${featureIndex}][title]" value=""
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Feature Title">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="about_features[${featureIndex}][description]" value=""
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Feature description">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', featureHtml);
    featureIndex++;
}

function addService() {
    const container = document.getElementById('services-container');
    const serviceHtml = `
        <div class="service-item bg-white p-4 rounded-lg border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Service Title</label>
                    <input type="text" name="services[${serviceIndex}][title]" value=""
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Service Title">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="services[${serviceIndex}][description]" value=""
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Service description">
                </div>
            </div>
            <button type="button" onclick="removeService(this)" class="mt-2 text-xs text-red-600 hover:text-red-800">
                Remove
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', serviceHtml);
    serviceIndex++;
}

function removeService(button) {
    button.closest('.service-item').remove();
}
</script>
@endsection

