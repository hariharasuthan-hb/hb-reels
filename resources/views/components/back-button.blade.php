@php
    $previousUrl = url()->previous();
    $currentUrl = url()->full();
    $defaultUrl = $defaultUrl ?? route('member.dashboard');

    // Avoid looping back to the same page or login routes
    if (
        empty($previousUrl) ||
        $previousUrl === $currentUrl ||
        str_contains($previousUrl, route('login'))
    ) {
        $previousUrl = $defaultUrl;
    }
@endphp

<div class="bg-gray-50 border-b border-gray-100">
    <div class="container mx-auto px-4 py-3 flex justify-end">
        <a href="{{ $previousUrl }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>
</div>

