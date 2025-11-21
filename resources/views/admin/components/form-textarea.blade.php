{{--
 | Form Textarea Component
 |
 | Reusable form textarea field component with optional rich text editor support.
 | Can be used as plain textarea or rich text editor based on $richText parameter.
 |
 | @param string $name - Input name attribute
 | @param string $label - Input label text
 | @param mixed $value - Input value
 | @param bool $required - Whether field is required
 | @param string|null $placeholder - Placeholder text
 | @param string|null $help - Help text to display below input
 | @param int $rows - Number of rows for plain textarea (default: 3)
 | @param int $colspan - Grid column span (1-12, default: 2)
 | @param array $attributes - Additional HTML attributes
 | @param bool $richText - Enable rich text editor (default: false)
 | @param int $richTextHeight - Rich text editor height in pixels (default: 400)
 | @param string $richTextToolbar - Rich text toolbar preset: 'full', 'basic', 'minimal' (default: 'full')
 | @param array $richTextPlugins - Rich text editor plugins
--}}
@php
    $name = $name ?? '';
    $label = $label ?? '';
    $value = $value ?? null;
    $required = $required ?? false;
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
    $rows = $rows ?? 3;
    $colspan = $colspan ?? 2;
    $attributes = $attributes ?? [];
    $richText = $richText ?? false;
    $richTextHeight = $richTextHeight ?? 400;
    $richTextToolbar = $richTextToolbar ?? 'full';
    $richTextPlugins = $richTextPlugins ?? ['lists', 'link', 'image', 'code'];
@endphp

@if($richText)
    {{-- Use rich text editor component --}}
    @include('admin.components.rich-text-editor', [
        'name' => $name,
        'label' => $label,
        'value' => $value,
        'required' => $required,
        'placeholder' => $placeholder,
        'help' => $help,
        'height' => $richTextHeight,
        'toolbar' => $richTextToolbar,
        'plugins' => $richTextPlugins,
        'colspan' => $colspan,
        'attributes' => $attributes,
    ])
@else
    {{-- Plain textarea --}}
    <div class="md:col-span-{{ $colspan }}">
        <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
        <textarea 
            name="{{ $name }}" 
            id="{{ $name }}" 
            rows="{{ $rows }}"
            @if($required) required @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
                   transition duration-200 ease-in-out
                   @error($name) border-red-500 focus:ring-red-500 @enderror
                   placeholder-gray-400 text-gray-900 bg-white resize-none"
            @foreach($attributes as $key => $val)
                {{ $key }}="{{ $val }}"
            @endforeach
        >{{ old($name, $value) }}</textarea>
        @if($help)
            <p class="mt-1 text-sm text-gray-500">{{ $help }}</p>
        @endif
        @error($name)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endif

