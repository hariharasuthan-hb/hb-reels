@csrf

@php
    $audienceOptions = [
        'all' => 'Everyone',
        'trainer' => 'Trainers only',
        'member' => 'Members only',
    ];
    $statusOptions = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label" for="title">Title<span class="text-red-500">*</span></label>
        <input type="text"
               name="title"
               id="title"
               value="{{ old('title', $announcement->title ?? '') }}"
               class="form-input w-full"
               required>
        @error('title')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="audience_type">Audience<span class="text-red-500">*</span></label>
        <select name="audience_type" id="audience_type" class="form-select w-full">
            @foreach($audienceOptions as $value => $label)
                <option value="{{ $value }}" {{ old('audience_type', $announcement->audience_type ?? 'all') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('audience_type')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="status">Status<span class="text-red-500">*</span></label>
        <select name="status" id="status" class="form-select w-full">
            @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" {{ old('status', $announcement->status ?? 'draft') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="published_at">Publish At</label>
        <input type="datetime-local"
               name="published_at"
               id="published_at"
               value="{{ old('published_at', isset($announcement->published_at) ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}"
               class="form-input w-full">
        @error('published_at')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="expires_at">Expires At</label>
        <input type="datetime-local"
               name="expires_at"
               id="expires_at"
               value="{{ old('expires_at', isset($announcement->expires_at) ? $announcement->expires_at->format('Y-m-d\TH:i') : '') }}"
               class="form-input w-full">
        @error('expires_at')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div class="md:col-span-2">
        <label class="form-label" for="body">Message<span class="text-red-500">*</span></label>
        <textarea name="body"
                  id="body"
                  rows="6"
                  class="form-input w-full"
                  required>{{ old('body', $announcement->body ?? '') }}</textarea>
        @error('body')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex gap-3 pt-6">
    <button type="submit" class="btn btn-primary">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">
        Cancel
    </a>
</div>

