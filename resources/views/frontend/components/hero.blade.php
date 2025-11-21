{{-- Hero Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsHero = $cmsHero ?? null;
    
    // Priority: CMS Content > Landing Page Content > Default
    $heroTitle = $cmsHero->title ?? $landingPage->welcome_title ?? 'Welcome to Our Gym';
    $heroSubtitle = $cmsHero->description ?? $cmsHero->content ?? $landingPage->welcome_subtitle ?? 'Transform your body, transform your life';
    // Priority: CMS background_image > CMS image > Landing Page background_image
    $heroImage = ($cmsHero && $cmsHero->background_image) 
        ? \Illuminate\Support\Facades\Storage::url($cmsHero->background_image)
        : (($cmsHero && $cmsHero->image) 
            ? \Illuminate\Support\Facades\Storage::url($cmsHero->image) 
            : ($landingPage && $landingPage->hero_background_image 
                ? \Illuminate\Support\Facades\Storage::url($landingPage->hero_background_image) 
                : null));
    $heroLink = $cmsHero->link ?? '#register';
    $heroLinkText = $cmsHero->link_text ?? 'Join Now';
    
    $bgStyle = $heroImage ? "background-image: url('{$heroImage}'); background-size: cover; background-position: center; background-blend-mode: overlay;" : '';
@endphp
<section class="hero-section {{ !$heroImage ? 'bg-gradient-to-r from-blue-600 to-purple-600' : '' }} text-white py-20 relative" style="{{ $bgStyle }}">
    @if($heroImage)
        <div class="absolute inset-0 bg-black bg-opacity-50 z-0"></div>
    @endif
    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center">
            <h1 class="text-5xl font-bold mb-4">{{ $heroTitle }}</h1>
            <p class="text-xl mb-8">{{ $heroSubtitle }}</p>
            <div class="flex justify-center gap-4">
                <a href="{{ $heroLink }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    {{ $heroLinkText }}
                </a>
                <a href="#about" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">
                    Learn More
                </a>
            </div>
        </div>
    </div>
</section>

