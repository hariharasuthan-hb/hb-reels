{{-- About Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsAbout = $cmsAbout ?? null;
    $cmsFeatures = $cmsFeatures ?? collect();
    
    // Priority: CMS Content > Landing Page Content > Default
    $aboutTitle = ($cmsAbout && $cmsAbout->title) ? $cmsAbout->title : ($landingPage->about_title ?? 'About Us');
    $aboutDescription = ($cmsAbout && ($cmsAbout->content ?? $cmsAbout->description)) 
        ? ($cmsAbout->content ?? $cmsAbout->description) 
        : ($landingPage->about_description ?? 'We are dedicated to helping you achieve your fitness goals with state-of-the-art equipment, expert trainers, and a supportive community.');
    $aboutImage = ($cmsAbout && $cmsAbout->image)
        ? \Illuminate\Support\Facades\Storage::url($cmsAbout->image)
        : null;
    // Priority: CMS background_video > CMS background_image
    $aboutBackgroundVideo = ($cmsAbout && $cmsAbout->video_path && $cmsAbout->video_is_background)
        ? \Illuminate\Support\Facades\Storage::url($cmsAbout->video_path)
        : null;
    $aboutBackgroundImage = ($cmsAbout && $cmsAbout->background_image && !$aboutBackgroundVideo)
        ? \Illuminate\Support\Facades\Storage::url($cmsAbout->background_image)
        : null;
    
    // Features: Use CMS features if available, otherwise use landing page features, otherwise default
    $features = [];
    if ($cmsFeatures->isNotEmpty()) {
        foreach ($cmsFeatures as $feature) {
            $features[] = [
                'icon' => $feature->extra_data['icon'] ?? '💪',
                'title' => $feature->title,
                'description' => $feature->description ?? $feature->content ?? '',
                'image' => $feature->image ? \Illuminate\Support\Facades\Storage::url($feature->image) : null,
                'title_color' => $feature->title_color,
                'content_color' => $feature->content_color,
            ];
        }
    } elseif ($landingPage && $landingPage->about_features) {
        $features = $landingPage->about_features;
    } else {
        $features = [
            ['icon' => '💪', 'title' => 'Expert Trainers', 'description' => 'Certified professionals to guide you', 'title_color' => null, 'content_color' => null],
            ['icon' => '🏋️', 'title' => 'Modern Equipment', 'description' => 'Latest fitness equipment available', 'title_color' => null, 'content_color' => null],
            ['icon' => '👥', 'title' => 'Community Support', 'description' => 'Join a supportive fitness community', 'title_color' => null, 'content_color' => null],
        ];
    }
@endphp
@php
    $aboutBgStyle = $aboutBackgroundImage
        ? "background-image: url('{$aboutBackgroundImage}'); background-size: cover; background-position: center; background-attachment: fixed;"
        : '';
    // Only show section if there's content to display
    $hasContent = $aboutTitle || $aboutDescription || (!empty($features) && count(array_filter($features, fn($f) => !empty($f['title']))) > 0);
@endphp
@if($hasContent)
<section id="about" class="py-20 {{ $aboutBackgroundImage || $aboutBackgroundVideo ? 'relative' : 'bg-gray-50' }}" style="{{ $aboutBgStyle }}">
    @if($aboutBackgroundVideo)
        <video autoplay muted loop class="absolute inset-0 w-full h-full object-cover z-0">
            <source src="{{ $aboutBackgroundVideo }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="absolute inset-0 bg-black bg-opacity-30 z-0"></div>
    @elseif($aboutBackgroundImage)
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @endif
    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4 {{ $aboutBackgroundImage || $aboutBackgroundVideo ? 'text-white' : 'text-gray-900' }}" style="color: {{ $cmsAbout->title_color ?? ($aboutBackgroundImage || $aboutBackgroundVideo ? '#ffffff' : '#111827') }};">{{ $aboutTitle }}</h2>
        </div>

        {{-- About Content --}}
        @if($aboutImage)
            {{-- Layout with Image --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
                <div class="order-2 lg:order-1">
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl group">
                        <img src="{{ $aboutImage }}" alt="{{ $aboutTitle }}"
                             class="w-full h-[500px] object-cover transition-transform duration-300 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="space-y-6">
                        <h3 class="text-6xl font-bold {{ $aboutBackgroundImage || $aboutBackgroundVideo ? 'text-white' : 'text-gray-900' }}" style="color: {{ $cmsAbout->title_color ?? ($aboutBackgroundImage || $aboutBackgroundVideo ? '#ffffff' : '#111827') }};">Why Choose Us?</h3>
                        @if($aboutDescription)
                            <p class="{{ $aboutBackgroundImage || $aboutBackgroundVideo ? 'text-white' : 'text-gray-700' }} text-lg leading-relaxed" style="color: {{ $cmsAbout->description_color ?? ($aboutBackgroundImage || $aboutBackgroundVideo ? '#ffffff' : '#374151') }};">
                                {!! $aboutDescription !!}
                            </p>
                        @endif
                        @if($cmsAbout && $cmsAbout->link)
                            <a href="{{ $cmsAbout->link }}"
                               class="inline-block mt-4 px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition duration-200 shadow-lg hover:shadow-xl">
                                {{ $cmsAbout->link_text ?? 'Learn More' }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($aboutDescription)
            {{-- Layout without Image - Centered Description --}}
            <div class="max-w-6xl mx-auto mb-12">
                <p class="{{ $aboutBackgroundImage || $aboutBackgroundVideo ? 'text-white' : 'text-gray-700' }} text-lg leading-relaxed text-center" style="color: {{ $cmsAbout->description_color ?? ($aboutBackgroundImage || $aboutBackgroundVideo ? '#ffffff' : '#374151') }};">
                    {!! $aboutDescription !!}
                </p>
            </div>
        @endif
        
        {{-- Features Grid --}}
        @if(!empty($features))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($features as $feature)
                    <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300 text-center">
                        @if(isset($feature['image']) && $feature['image'])
                            <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] ?? 'Feature' }}" 
                                 class="h-20 w-20 mx-auto mb-6 rounded-full object-cover ring-4 ring-blue-100">
                        @else
                            <div class="text-5xl mb-6">{{ $feature['icon'] ?? '💪' }}</div>
                        @endif
                        <h3 class="text-xl font-bold mb-3 text-gray-900" style="color: {{ $feature['title_color'] ?? $cmsAbout->title_color ?? '#111827' }};">{{ $feature['title'] ?? 'Feature' }}</h3>
                        <p class="text-gray-600 leading-relaxed" style="color: {{ $feature['content_color'] ?? $cmsAbout->content_color ?? '#4b5563' }};">{!! $feature['description'] ?? '' !!}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif

