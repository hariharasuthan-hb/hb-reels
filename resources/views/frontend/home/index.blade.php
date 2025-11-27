@extends('frontend.layouts.app')

@section('content')
    {{-- Banner Carousel Section --}}
    @include('frontend.components.banner-carousel')
    
    {{-- Hero Section (Fallback if no banners) --}}
    @php
        $banners = \App\Models\Banner::getActiveBanners();
    @endphp
    @if($banners->isEmpty())
        @include('frontend.components.hero', [
            'landingPage' => $landingPage ?? null,
            'cmsHero' => $cmsHero ?? null
        ])
    @endif
    
    {{-- About Section --}}
    @if(isset($cmsAbout) || isset($cmsFeatures) && $cmsFeatures->isNotEmpty() || (isset($landingPage) && $landingPage->about_title))
        @include('frontend.components.about', [
            'landingPage' => $landingPage ?? null,
            'cmsAbout' => $cmsAbout ?? null,
            'cmsFeatures' => $cmsFeatures ?? collect()
        ])
    @endif

    {{-- Services Section --}}
    @include('frontend.components.services', [
        'landingPage' => $landingPage ?? null,
        'cmsServicesSection' => $cmsServicesSection ?? null,
        'cmsServices' => $cmsServices ?? collect()
    ])
    
    {{-- Testimonials Section --}}
    @include('frontend.components.testimonials', [
        'cmsTestimonialsSection' => $cmsTestimonialsSection ?? null,
        'cmsTestimonials' => $cmsTestimonials ?? collect()
    ])
    
    {{-- Contact Section --}}
    @include('frontend.components.contact-form')
@endsection

