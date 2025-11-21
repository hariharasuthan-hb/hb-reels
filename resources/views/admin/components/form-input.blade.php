{{--
 | Form Input Component
 |
 | Reusable form input field component with label, validation, and help text.
 |
 | @param string $name - Input name attribute
 | @param string $label - Input label text
 | @param string $type - Input type (text, email, password, number, date, etc.)
 | @param mixed $value - Input value
 | @param bool $required - Whether field is required
 | @param string|null $placeholder - Placeholder text
 | @param string|null $help - Help text to display below input
 | @param int $colspan - Grid column span (1-12)
 | @param array $attributes - Additional HTML attributes
--}}
@php
    $name = $name ?? '';
    $label = $label ?? '';
    $type = $type ?? 'text';
    $value = $value ?? null;
    $required = $required ?? false;
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
    $colspan = $colspan ?? 1;
    $attributes = $attributes ?? [];
@endphp

<div class="md:col-span-{{ $colspan }}">
    <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <div class="relative">
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ old($name, $value) }}"
            @if($required) required @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
                   transition duration-200 ease-in-out
                   @error($name) border-red-500 focus:ring-red-500 @enderror
                   placeholder-gray-400 text-gray-900 bg-white"
            @foreach($attributes as $key => $val)
                {{ $key }}="{{ $val }}"
            @endforeach
        >
        @error($name)
            <svg class="absolute right-3 top-3 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        @enderror
    </div>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

