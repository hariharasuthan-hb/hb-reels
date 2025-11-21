{{--
 | Rich Text Editor Component
 |
 | Reusable rich text editor component using TinyMCE.
 | Provides WYSIWYG editing with proper HTML encoding/decoding handling.
 |
 | @param string $name - Input name attribute (required)
 | @param string $label - Input label text (required)
 | @param mixed $value - Input value (HTML content)
 | @param bool $required - Whether field is required
 | @param string|null $placeholder - Placeholder text
 | @param string|null $help - Help text to display below editor
 | @param int $height - Editor height in pixels (default: 400)
 | @param string $toolbar - Toolbar preset: 'full', 'basic', or 'minimal' (default: 'full')
 | @param array $plugins - Additional plugins to enable
 | @param int $colspan - Grid column span (1-12, default: 2)
 | @param array $attributes - Additional HTML attributes
 |
 | Features:
 | - WYSIWYG editing with TinyMCE
 | - Proper HTML encoding/decoding (no double encoding)
 | - Configurable toolbar and plugins
 | - Responsive design
 | - Error handling and validation display
--}}
@php
    $name = $name ?? '';
    $label = $label ?? '';
    $value = $value ?? null;
    $required = $required ?? false;
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
    $height = $height ?? 400;
    $toolbar = $toolbar ?? 'full';
    $plugins = $plugins ?? ['lists', 'link', 'image', 'code'];
    $colspan = $colspan ?? 2;
    $attributes = $attributes ?? [];
    
    // Get old value or model value, ensuring HTML is not double-encoded
    $editorValue = old($name, $value ?? '');
    
    // Toolbar presets
    $toolbarConfigs = [
        'full' => 'undo redo | formatselect | bold italic underline strikethrough | 
                   alignleft aligncenter alignright alignjustify | 
                   bullist numlist outdent indent | link image code | removeformat',
        'basic' => 'bold italic underline | bullist numlist | link | removeformat',
        'minimal' => 'bold italic | removeformat',
    ];
    
    $toolbarConfig = $toolbarConfigs[$toolbar] ?? $toolbarConfigs['full'];
    
    // Generate unique ID for this editor instance
    $editorId = 'rich-text-editor-' . str_replace(['[', ']'], ['-', ''], $name) . '-' . uniqid();
@endphp

<div class="md:col-span-{{ $colspan }}">
    <label for="{{ $editorId }}" class="block text-sm font-semibold text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    {{-- Hidden textarea that will be converted to TinyMCE --}}
    <textarea 
        name="{{ $name }}" 
        id="{{ $editorId }}" 
        @if($required) required @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        class="rich-text-editor @error($name) border-red-500 @enderror"
        data-toolbar="{{ $toolbar }}"
        data-height="{{ $height }}"
        data-plugins="{{ json_encode($plugins) }}"
        @foreach($attributes as $key => $val)
            {{ $key }}="{{ $val }}"
        @endforeach
    >{!! $editorValue !!}</textarea>
    
    @if($help)
        <p class="mt-1 text-sm text-gray-500">{{ $help }}</p>
    @endif
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

{{-- TinyMCE styles are imported via JavaScript --}}

@push('scripts')
    <script>
        // Initialize this editor when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initRichTextEditor === 'function') {
                initRichTextEditor('{{ $editorId }}', {
                    toolbar: '{{ $toolbarConfig }}',
                    height: {{ $height }},
                    plugins: {!! json_encode($plugins) !!}
                });
            }
        });
    </script>
@endpush

