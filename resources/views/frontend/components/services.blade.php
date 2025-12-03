{{-- Services Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsServicesSection = $cmsServicesSection ?? null;
    $cmsServices = $cmsServices ?? collect();
    
    // Priority: CMS Content > Landing Page Content > Default
    $servicesTitle = ($cmsServicesSection && $cmsServicesSection->title) 
        ? $cmsServicesSection->title 
        : ($landingPage->services_title ?? 'Our Services');
    $servicesDescription = ($cmsServicesSection && ($cmsServicesSection->description ?? $cmsServicesSection->content)) 
        ? ($cmsServicesSection->description ?? $cmsServicesSection->content) 
        : ($landingPage->services_description ?? 'Choose from our range of fitness programs and services');
    
    // Check for background video/image: First check services-section, then check all services content
    // Priority: background_video > background_image
    $servicesBackgroundVideo = null;
    $servicesBackgroundImage = null;

    // Check services section first
    if ($cmsServicesSection) {
        if ($cmsServicesSection->video_path && $cmsServicesSection->video_is_background) {
            $servicesBackgroundVideo = \Illuminate\Support\Facades\Storage::url($cmsServicesSection->video_path);
        } elseif ($cmsServicesSection->background_image) {
            $servicesBackgroundImage = \Illuminate\Support\Facades\Storage::url($cmsServicesSection->background_image);
        }
    }

    // If no background found in section, check all services content
    if (!$servicesBackgroundVideo && !$servicesBackgroundImage) {
        $cmsContentRepo = app(\App\Repositories\Interfaces\CmsContentRepositoryInterface::class);
        $allServicesForBg = $cmsContentRepo->getFrontendContent('services');

        // Find first service with background_video, then background_image
        foreach ($allServicesForBg as $serviceItem) {
            if ($serviceItem->video_path && $serviceItem->video_is_background && !$servicesBackgroundVideo) {
                $servicesBackgroundVideo = \Illuminate\Support\Facades\Storage::url($serviceItem->video_path);
            } elseif ($serviceItem->background_image && !$servicesBackgroundImage && !$servicesBackgroundVideo) {
                $servicesBackgroundImage = \Illuminate\Support\Facades\Storage::url($serviceItem->background_image);
            }
        }
    }
    
    // Services: Use CMS services if available, otherwise use landing page services, otherwise default
    $services = [];
    if ($cmsServices->isNotEmpty()) {
        foreach ($cmsServices as $service) {
            $services[] = [
                'title' => $service->title,
                'description' => $service->description ?? $service->content ?? '',
                'image' => $service->image ? \Illuminate\Support\Facades\Storage::url($service->image) : null,
                'link' => $service->link ?? '#contact',
                'link_text' => $service->link_text ?? 'Learn More',
                'title_color' => $service->title_color,
                'content_color' => $service->content_color,
            ];
        }
    } elseif ($landingPage && $landingPage->services) {
        $services = $landingPage->services;
    } else {
        $services = [
            ['title' => 'Personal Training', 'description' => 'One-on-one training sessions with expert trainers', 'title_color' => null, 'content_color' => null],
            ['title' => 'Group Classes', 'description' => 'Join group fitness classes for motivation', 'title_color' => null, 'content_color' => null],
            ['title' => 'Wellness Coaching', 'description' => 'Holistic guidance for balanced habits', 'title_color' => null, 'content_color' => null],
            ['title' => 'Cardio Zone', 'description' => 'State-of-the-art cardio equipment', 'title_color' => null, 'content_color' => null],
            ['title' => 'Weight Training', 'description' => 'Comprehensive weight training facilities', 'title_color' => null, 'content_color' => null],
            ['title' => 'Yoga & Meditation', 'description' => 'Relax and rejuvenate with yoga classes', 'title_color' => null, 'content_color' => null],
        ];
    }
@endphp
@php
    $servicesBgStyle = $servicesBackgroundImage
        ? "background-image: url('{$servicesBackgroundImage}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;"
        : '';
@endphp
<section id="services" class="py-20 {{ $servicesBackgroundImage || $servicesBackgroundVideo ? 'relative min-h-[600px]' : '' }}" style="{{ $servicesBgStyle }}">
    @if($servicesBackgroundVideo)
        <video autoplay muted loop class="absolute inset-0 w-full h-full object-cover z-0">
            <source src="{{ $servicesBackgroundVideo }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="absolute inset-0 bg-black bg-opacity-30 z-0"></div>
    @elseif($servicesBackgroundImage)
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @endif
    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4 {{ $servicesBackgroundImage || $servicesBackgroundVideo ? 'text-white' : 'text-gray-900' }}" style="color: {{ $cmsServicesSection->title_color ?? ($servicesBackgroundImage || $servicesBackgroundVideo ? '#ffffff' : '#111827') }};">{{ $servicesTitle }}</h2>
            <p class="{{ $servicesBackgroundImage || $servicesBackgroundVideo ? 'text-white' : 'text-gray-600' }} max-w-2xl mx-auto" style="color: {{ $cmsServicesSection->description_color ?? ($servicesBackgroundImage || $servicesBackgroundVideo ? '#ffffff' : '#4b5563') }};">
                {!! $servicesDescription !!}
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($services as $service)
                <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                    @if(isset($service['image']) && $service['image'])
                        <img src="{{ $service['image'] }}" alt="{{ $service['title'] ?? 'Service' }}" class="w-full h-48 object-cover rounded-lg mb-4">
                    @endif
                    <h3 class="text-2xl font-semibold mb-3" style="color: {{ $service['title_color'] ?? $cmsServicesSection->title_color ?? '#1f2937' }};">{{ $service['title'] ?? 'Service' }}</h3>
                    <p class="text-gray-600 mb-4" style="color: {{ $service['content_color'] ?? $cmsServicesSection->content_color ?? '#4b5563' }};">{!! $service['description'] ?? '' !!}</p>
                    <a href="{{ $service['link'] ?? '#contact' }}" class="text-blue-600 font-semibold">
                        {{ $service['link_text'] ?? 'Learn More' }} →
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

