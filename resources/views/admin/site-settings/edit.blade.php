@extends('admin.layouts.app')

@section('page-title', 'Site Settings')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Site Settings</h1>
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

    {{-- Form --}}
    <form action="{{ route('admin.site-settings.update', $settings->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-8">
        @csrf
        @method('PUT')

        <div class="space-y-8">
            {{-- Basic Information Section --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Basic Information
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @include('admin.components.form-input', [
                        'name' => 'site_title',
                        'label' => 'Site Title',
                        'value' => $settings->site_title ?? 'Gym Management',
                        'required' => true,
                        'placeholder' => 'Enter site title',
                        'colspan' => 2,
                    ])
                    
                    {{-- Logo Upload --}}
                    <div class="md:col-span-2">
                        <label for="logo" class="block text-sm font-semibold text-gray-700 mb-2">
                            Site Logo
                        </label>
                        <div class="flex items-center space-x-6">
                            @if($settings->logo)
                                <div class="flex-shrink-0">
                                    <img src="{{ Storage::url($settings->logo) }}" 
                                         alt="Current Logo" 
                                         class="h-20 w-auto object-contain border border-gray-300 rounded-lg p-2 bg-white">
                                </div>
                            @endif
                            <div class="flex-1">
                                <input 
                                    type="file" 
                                    name="logo" 
                                    id="logo" 
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-lg p-2"
                                >
                                <p class="mt-2 text-xs text-gray-500">Recommended: PNG or SVG, max 2MB. Current logo will be replaced.</p>
                            </div>
                        </div>
                        @error('logo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Contact Information Section --}}
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl border border-green-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Contact Information
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @include('admin.components.form-input', [
                        'name' => 'contact_email',
                        'label' => 'Contact Email',
                        'type' => 'email',
                        'value' => $settings->contact_email ?? null,
                        'placeholder' => 'info@example.com',
                    ])
                    
                    @include('admin.components.form-input', [
                        'name' => 'contact_mobile',
                        'label' => 'Contact Mobile Number',
                        'type' => 'tel',
                        'value' => $settings->contact_mobile ?? null,
                        'placeholder' => '+1 (555) 123-4567',
                    ])
                    
                    @include('admin.components.form-textarea', [
                        'name' => 'address',
                        'label' => 'Address',
                        'value' => $settings->address ?? null,
                        'placeholder' => '123 Fitness Street, City, State 12345',
                        'rows' => 3,
                        'colspan' => 2,
                    ])
                </div>
            </div>

            {{-- Social Media Links Section (Optional) --}}
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-xl border border-purple-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Social Media Links (Optional)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @include('admin.components.form-input', [
                        'name' => 'facebook_url',
                        'label' => 'Facebook URL',
                        'type' => 'url',
                        'value' => $settings->facebook_url ?? null,
                        'placeholder' => 'https://facebook.com/yourpage',
                    ])
                    
                    @include('admin.components.form-input', [
                        'name' => 'twitter_url',
                        'label' => 'Twitter URL',
                        'type' => 'url',
                        'value' => $settings->twitter_url ?? null,
                        'placeholder' => 'https://twitter.com/yourhandle',
                    ])
                    
                    @include('admin.components.form-input', [
                        'name' => 'instagram_url',
                        'label' => 'Instagram URL',
                        'type' => 'url',
                        'value' => $settings->instagram_url ?? null,
                        'placeholder' => 'https://instagram.com/yourhandle',
                    ])
                    
                    @include('admin.components.form-input', [
                        'name' => 'linkedin_url',
                        'label' => 'LinkedIn URL',
                        'type' => 'url',
                        'value' => $settings->linkedin_url ?? null,
                        'placeholder' => 'https://linkedin.com/company/yourcompany',
                    ])
                </div>
            </div>

            {{-- Footer Settings Section --}}
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-6 rounded-xl border border-orange-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Footer Settings
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @include('admin.components.form-input', [
                        'name' => 'footer_partner',
                        'label' => 'Footer Partner Text',
                        'value' => $settings->footer_partner ?? null,
                        'placeholder' => '@hbitpartner',
                        'help' => 'This text will appear in the footer copyright line (e.g., @hbitpartner)',
                        'colspan' => 2,
                    ])
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="mt-8 flex justify-end space-x-4">
            <a href="{{ route('admin.dashboard') }}" 
               class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-semibold">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Settings
                </span>
            </button>
        </div>
    </form>
</div>
@endsection

