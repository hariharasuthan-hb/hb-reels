{{--
 | Form Select Component
 |
 | Reusable form select dropdown component with label, validation, and help text.
 |
 | @param string $name - Select name attribute
 | @param string $label - Select label text
 | @param array $options - Select options (key => value pairs)
 | @param mixed $value - Selected value
 | @param bool $required - Whether field is required
 | @param string $placeholder - Placeholder text for select
 | @param string|null $help - Help text to display below select
 | @param int $colspan - Grid column span (1-12)
 | @param array $attributes - Additional HTML attributes
--}}
@php
    $name = $name ?? '';
    $label = $label ?? '';
    $options = $options ?? [];
    $value = $value ?? null;
    $required = $required ?? false;
    $placeholder = $placeholder ?? 'Select an option';
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
        <select 
            name="{{ $name }}" 
            id="{{ $name }}" 
            @if($required) required @endif
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
                   transition duration-200 ease-in-out
                   @error($name) border-red-500 focus:ring-red-500 @enderror
                   text-gray-900 bg-white appearance-none
                   bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3E%3Cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3E%3C/svg%3E')] 
                   bg-[length:1.5em_1.5em] bg-[right_0.75rem_center] bg-no-repeat pr-10"
            @foreach($attributes as $key => $val)
                {{ $key }}="{{ $val }}"
            @endforeach
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" 
                        {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
        @error($name)
            <svg class="absolute right-10 top-3 h-5 w-5 text-red-500 pointer-events-none" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        @enderror
    </div>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

