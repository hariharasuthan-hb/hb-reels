@php
    $name = $name ?? '';
    $label = $label ?? '';
    $options = $options ?? [];
    $selected = $selected ?? [];
    $help = $help ?? null;
    $colspan = $colspan ?? 2;
@endphp

<div class="md:col-span-{{ $colspan }}">
    <label class="block text-sm font-semibold text-gray-700 mb-3">
        {{ $label }}
    </label>
    <div class="space-y-3 bg-gray-50 p-4 rounded-lg border border-gray-200">
        @foreach($options as $optionValue => $optionLabel)
            <label class="flex items-center group cursor-pointer hover:bg-white p-2 rounded transition-colors duration-150">
                <input 
                    type="checkbox" 
                    name="{{ $name }}[]" 
                    value="{{ $optionValue }}"
                    {{ in_array($optionValue, old($name, $selected)) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded 
                           transition duration-150 cursor-pointer"
                >
                <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-gray-900">
                    {{ $optionLabel }}
                </span>
            </label>
        @endforeach
    </div>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

