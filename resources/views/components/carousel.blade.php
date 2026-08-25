@props([
    'perViewSm' => 1,
    'perViewMd' => 2,
    'perViewLg' => 3,
    'gap' => 32,
    'autoplay' => false,
    'interval' => 5000,
    'loop' => false,
    'showArrows' => true,
    'showDots' => true,
    'arrowClass' => 'bg-white/90 hover:bg-white text-gray-800 shadow-md',
    'dotActiveClass' => 'bg-white',
    'dotInactiveClass' => 'bg-white/50',
])

@php
    $config = [
        'perViewSm' => (int) $perViewSm,
        'perViewMd' => (int) $perViewMd,
        'perViewLg' => (int) $perViewLg,
        'gap' => (int) $gap,
        'autoplay' => filter_var($autoplay, FILTER_VALIDATE_BOOLEAN),
        'interval' => (int) $interval,
        'loop' => filter_var($loop, FILTER_VALIDATE_BOOLEAN),
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="carousel(@js($config))"
    @mouseenter="stopAutoplay()"
    @mouseleave="if (autoplay && showControls) startAutoplay()"
>
    <div class="flex items-center gap-2 md:gap-4">
        @if($showArrows)
            <div class="shrink-0 w-10 md:w-12 flex justify-center">
                <button
                    type="button"
                    x-show="showControls"
                    x-cloak
                    @click="prev()"
                    :disabled="!loop && current === 0"
                    :class="{ 'opacity-40 cursor-not-allowed': !loop && current === 0 }"
                    class="p-2 md:p-3 rounded-full transition {{ $arrowClass }}"
                    aria-label="Previous slide"
                >
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>
        @endif

        <div class="overflow-hidden flex-1 min-w-0" x-ref="viewport">
            <div
                class="flex transition-transform duration-500 ease-out"
                x-ref="track"
                :style="trackStyle"
            >
                {{ $slot }}
            </div>
        </div>

        @if($showArrows)
            <div class="shrink-0 w-10 md:w-12 flex justify-center">
                <button
                    type="button"
                    x-show="showControls"
                    x-cloak
                    @click="next()"
                    :disabled="!loop && current >= maxIndex"
                    :class="{ 'opacity-40 cursor-not-allowed': !loop && current >= maxIndex }"
                    class="p-2 md:p-3 rounded-full transition {{ $arrowClass }}"
                    aria-label="Next slide"
                >
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    @if($showDots)
        <div
            class="flex justify-center gap-2 mt-6"
            x-show="showControls && dotCount > 1"
            x-cloak
        >
            <template x-for="i in dotCount" :key="i">
                <button
                    type="button"
                    @click="goTo(i - 1)"
                    class="w-3 h-3 rounded-full transition"
                    :class="current === (i - 1) ? '{{ $dotActiveClass }}' : '{{ $dotInactiveClass }}'"
                    :aria-label="'Go to slide ' + i"
                ></button>
            </template>
        </div>
    @endif
</div>
