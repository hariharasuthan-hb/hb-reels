@csrf

@php
    $audienceOptions = [
        'all' => 'Everyone',
        'trainer' => 'Trainers only',
        'member' => 'Members only',
        'user' => 'Specific user',
    ];
    $statusOptions = [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'archived' => 'Archived',
    ];
@endphp

<div x-data="{ audience: '{{ old('audience_type', $notification->audience_type ?? 'all') }}' }" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label" for="title">Title<span class="text-red-500">*</span></label>
            <input type="text"
                   name="title"
                   id="title"
                   value="{{ old('title', $notification->title ?? '') }}"
                   class="form-input w-full"
                   required>
            @error('title')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="form-label" for="audience_type">Audience<span class="text-red-500">*</span></label>
            <select name="audience_type"
                    id="audience_type"
                    class="form-select w-full"
                    x-model="audience">
                @foreach($audienceOptions as $value => $label)
                    <option value="{{ $value }}" {{ old('audience_type', $notification->audience_type ?? 'all') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('audience_type')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div x-show="audience === 'user'" class="md:col-span-2" x-cloak>
            <label class="form-label" for="target_user_id">Select User<span class="text-red-500">*</span></label>
            <select name="target_user_id" id="target_user_id" class="form-select w-full">
                <option value="">Choose user</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string) old('target_user_id', $notification->target_user_id ?? '') === (string) $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
            @error('target_user_id')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="form-label" for="status">Status<span class="text-red-500">*</span></label>
            <select name="status" id="status" class="form-select w-full">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $notification->status ?? 'draft') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="form-label" for="scheduled_for">Schedule For</label>
            <input type="datetime-local"
                   name="scheduled_for"
                   id="scheduled_for"
                   value="{{ old('scheduled_for', isset($notification->scheduled_for) ? $notification->scheduled_for->format('Y-m-d\TH:i') : '') }}"
                   class="form-input w-full">
            @error('scheduled_for')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="form-label" for="published_at">Publish At</label>
            <input type="datetime-local"
                   name="published_at"
                   id="published_at"
                   value="{{ old('published_at', isset($notification->published_at) ? $notification->published_at->format('Y-m-d\TH:i') : '') }}"
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
                   value="{{ old('expires_at', isset($notification->expires_at) ? $notification->expires_at->format('Y-m-d\TH:i') : '') }}"
                   class="form-input w-full">
            @error('expires_at')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-2">
            <label class="flex items-center gap-2">
                <input type="checkbox"
                       name="requires_acknowledgement"
                       value="1"
                       class="form-checkbox"
                       {{ old('requires_acknowledgement', $notification->requires_acknowledgement ?? false) ? 'checked' : '' }}>
                <span class="form-label mb-0">Requires acknowledgement</span>
            </label>
            <p class="text-xs text-gray-500">If enabled, recipients must mark the notification as read.</p>
        </div>
        <div class="md:col-span-2">
            <label class="form-label" for="message">Message<span class="text-red-500">*</span></label>
            <textarea name="message"
                      id="message"
                      rows="6"
                      class="form-input w-full"
                      required>{{ old('message', $notification->message ?? '') }}</textarea>
            @error('message')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
            Cancel
        </a>
    </div>
</div>

