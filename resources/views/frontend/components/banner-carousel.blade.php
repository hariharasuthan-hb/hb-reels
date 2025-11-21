{{-- Banner Carousel Section --}}
@php
    $banners = \App\Models\Banner::getActiveBanners();
@endphp
@if($banners->isNotEmpty())
<section class="banner-carousel relative h-[700px] md:h-[800px] overflow-hidden">
    <div id="banner-carousel" class="relative h-full">
        @foreach($banners as $index => $banner)
            <div class="banner-slide absolute inset-0 transition-opacity duration-1000 {{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}" 
                 data-slide="{{ $index }}">
                @php
                    $imageUrl = $banner->image ? \Illuminate\Support\Facades\Storage::url($banner->image) : null;
                    $overlayColor = $banner->overlay_color ?? '#000000';
                    $overlayOpacity = $banner->overlay_opacity ?? 0.5;
                    // Convert hex to RGB
                    $hex = str_replace('#', '', $overlayColor);
                    $r = hexdec(substr($hex, 0, 2));
                    $g = hexdec(substr($hex, 2, 2));
                    $b = hexdec(substr($hex, 4, 2));
                    $rgba = "rgba({$r}, {$g}, {$b}, {$overlayOpacity})";
                @endphp
                <div class="relative h-full w-full" 
                     style="background-image: url('{{ $imageUrl }}'); background-size: cover; background-position: center;">
                    <div class="absolute inset-0" style="background-color: {{ $rgba }};"></div>
                    <div class="container mx-auto px-4 h-full flex items-center relative z-10">
                        <div class="text-center text-white max-w-4xl mx-auto">
                            @if($banner->title)
                                @php
                                    // Process title to make "Burn" and "Build" bold and italic
                                    $title = $banner->title;
                                    $title = preg_replace('/\b(Burn)\b/i', '<span class="font-bold italic">$1</span>', $title);
                                    $title = preg_replace('/\b(Build)\b/i', '<span class="font-bold italic">$1</span>', $title);
                                @endphp
                                <h1 class="text-5xl md:text-6xl font-bold mb-4 animate-fade-in-up whitespace-nowrap">
                                    {!! $title !!}
                                </h1>
                            @endif
                            @if($banner->subtitle)
                                <p class="text-xl md:text-2xl mb-8 animate-fade-in-up-delay">
                                    {{ $banner->subtitle }}
                                </p>
                            @endif
                            @if($banner->link)
                                <a href="{{ $banner->link }}" 
                                   class="inline-block bg-white text-gray-900 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition transform hover:scale-105 animate-fade-in-up-delay-2">
                                    {{ $banner->link_text ?? 'Learn More' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- Navigation Arrows --}}
    @if($banners->count() > 1)
        <button id="prev-banner" 
                class="absolute left-4 top-1/2 transform -translate-y-1/2 z-20 bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-3 rounded-full transition backdrop-blur-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <button id="next-banner" 
                class="absolute right-4 top-1/2 transform -translate-y-1/2 z-20 bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-3 rounded-full transition backdrop-blur-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        
        {{-- Dots Indicator --}}
        <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 z-20 flex space-x-2">
            @foreach($banners as $index => $banner)
                <button class="banner-dot w-3 h-3 rounded-full transition {{ $index === 0 ? 'bg-white' : 'bg-white bg-opacity-50' }}" 
                        data-slide="{{ $index }}"></button>
            @endforeach
        </div>
    @endif
</section>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out;
}

.animate-fade-in-up-delay {
    animation: fade-in-up 0.8s ease-out 0.2s both;
}

.animate-fade-in-up-delay-2 {
    animation: fade-in-up 0.8s ease-out 0.4s both;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.banner-slide');
    const dots = document.querySelectorAll('.banner-dot');
    const prevBtn = document.getElementById('prev-banner');
    const nextBtn = document.getElementById('next-banner');
    let currentSlide = 0;
    let autoSlideInterval;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            if (i === index) {
                slide.classList.remove('opacity-0', 'z-0');
                slide.classList.add('opacity-100', 'z-10');
            } else {
                slide.classList.remove('opacity-100', 'z-10');
                slide.classList.add('opacity-0', 'z-0');
            }
        });

        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.remove('bg-opacity-50');
                dot.classList.add('bg-white');
            } else {
                dot.classList.remove('bg-white');
                dot.classList.add('bg-opacity-50');
            }
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }

    function startAutoSlide() {
        autoSlideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
    }

    function stopAutoSlide() {
        clearInterval(autoSlideInterval);
    }

    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); stopAutoSlide(); startAutoSlide(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); stopAutoSlide(); startAutoSlide(); });

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
            stopAutoSlide();
            startAutoSlide();
        });
    });

    // Auto-slide functionality
    if (slides.length > 1) {
        startAutoSlide();
        
        // Pause on hover
        const carousel = document.getElementById('banner-carousel');
        if (carousel) {
            carousel.addEventListener('mouseenter', stopAutoSlide);
            carousel.addEventListener('mouseleave', startAutoSlide);
        }
    }
});
</script>
@endif

