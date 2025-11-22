<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Event Reel Generator - HB Reels</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-600 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        {{-- Main Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Event Reel Generator</h1>

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-sm text-red-800">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route(config('eventreel.route_name_prefix') . 'generate') }}" method="POST" enctype="multipart/form-data" id="reel-form">
                @csrf

                {{-- Image Upload --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Upload Flyer Image
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-4h-4m-4 0h4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="flyer_image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span id="upload-text">Click to upload</span>
                                    <input id="flyer_image" name="flyer_image" type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png" onchange="handleFileSelect(this)">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG up to 10MB</p>
                            <p id="file-name" class="text-sm text-gray-600 mt-2 hidden"></p>
                        </div>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="relative mb-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">OR</span>
                    </div>
                </div>

                {{-- Event + AI Inputs --}}
                <div class="flex flex-col gap-6 mb-6">
                    <section>
                        <div class="rounded-3xl border border-gray-300 bg-white shadow-sm p-6 space-y-5">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">Event Details</h2>
                                <span class="text-xs font-semibold text-blue-600 uppercase tracking-widest">Optional</span>
                            </div>
                            <div class="grid gap-4">
                                <div>
                                    <label for="event_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Event Name
                                    </label>
                                    <input
                                        id="event_name"
                                        name="event_name"
                                        type="text"
                                        value="{{ old('event_name') }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g. Summer Sunset Party"
                                    >
                                </div>
                                <div>
                                    <label for="event_datetime" class="block text-sm font-medium text-gray-700 mb-1">
                                        Date &amp; Time
                                    </label>
                                    <input
                                        id="event_datetime"
                                        name="event_datetime"
                                        type="text"
                                        value="{{ old('event_datetime') }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g. Fri, Nov 21 • 7:00 PM"
                                    >
                                </div>
                                <div>
                                    <label for="event_location" class="block text-sm font-medium text-gray-700 mb-1">
                                        Location
                                    </label>
                                    <input
                                        id="event_location"
                                        name="event_location"
                                        type="text"
                                        value="{{ old('event_location') }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g. Rooftop R&C, Downtown"
                                    >
                                </div>
                                <div>
                                    <label for="event_highlights" class="block text-sm font-medium text-gray-700 mb-1">
                                        Highlights
                                    </label>
                                    <textarea
                                        id="event_highlights"
                                        name="event_highlights"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                        placeholder="e.g. Live DJ set, art installations, open bar"
                                    >{{ old('event_highlights') }}</textarea>
                                </div>
                                <div>
                                    <label for="event_call_to_action" class="block text-sm font-medium text-gray-700 mb-1">
                                        Call to Action
                                    </label>
                                    <input
                                        id="event_call_to_action"
                                        name="event_call_to_action"
                                        type="text"
                                        value="{{ old('event_call_to_action') }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g. RSVP now for early bird perks"
                                    >
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-gray-600">
                                <p class="font-semibold text-gray-900">Preview</p>
                                <dl class="space-y-2">
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500 tracking-wide">Event Name</dt>
                                        <dd id="preview-event-name" class="text-base text-gray-800">test</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500 tracking-wide">Date &amp; Time</dt>
                                        <dd id="preview-event-datetime" class="text-base text-gray-800">12-12-2026</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500 tracking-wide">Location</dt>
                                        <dd id="preview-event-location" class="text-base text-gray-800">cocai</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500 tracking-wide">Highlights</dt>
                                        <dd id="preview-event-highlights" class="text-base text-gray-800">&nbsp;</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase text-gray-500 tracking-wide">Call to Action</dt>
                                        <dd id="preview-event-cta" class="text-base text-gray-800">&nbsp;</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4 p-4 bg-gray-50 border border-gray-200 rounded-2xl">
                        <h2 class="text-lg font-semibold text-gray-900">AI Video Description</h2>
                        <p class="text-sm text-gray-500">Describe how the video should feel, tone, pacing, or any keywords for the AI.</p>
                    <textarea 
                        id="event_text" 
                        name="event_text" 
                            rows="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                            placeholder="Craft a short prompt for the AI—what should it focus on, what energy should it capture?">{{ old('event_text') }}</textarea>
                    </section>
                </div>

                {{-- Show Flyer Checkbox --}}
                <div class="mb-6">
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="show_flyer" 
                            value="1" 
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            {{ old('show_flyer') ? 'checked' : '' }}
                        >
                        <span class="ml-2 text-sm text-gray-700">Add background behind flyer</span>
                    </label>
                </div>

                {{-- Access Code (if configured) --}}
                @if(config('eventreel.access_code'))
                <div class="mb-6">
                    <label for="access_code" class="block text-sm font-medium text-gray-700 mb-2">
                        Access Code
                    </label>
                    <input 
                        type="text" 
                        id="access_code" 
                        name="access_code" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter access code"
                        value="{{ old('access_code') }}"
                    >
                </div>
                @endif

                {{-- Generate Button --}}
                <button 
                    type="submit" 
                    id="generate-btn"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-4 px-6 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                >
                    <span id="btn-text">Generate</span>
                    <span id="btn-loading" class="hidden">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generating...
                    </span>
                </button>
            </form>
        </div>

        {{-- Branding --}}
        <div class="mt-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-2">Planmyweekend.ai</h2>
            <p class="text-white text-sm">Instantly Find FREE Local Things To Do With Our AI</p>
        </div>
    </div>

    <script>
        function handleFileSelect(input) {
            const fileName = input.files[0]?.name;
            const fileNameEl = document.getElementById('file-name');
            const uploadText = document.getElementById('upload-text');
            
            if (fileName) {
                fileNameEl.textContent = fileName;
                fileNameEl.classList.remove('hidden');
                uploadText.textContent = 'Change file';
            } else {
                fileNameEl.classList.add('hidden');
                uploadText.textContent = 'Click to upload';
            }
        }

        const reelForm = document.getElementById('reel-form');
        const generateBtn = document.getElementById('generate-btn');
        const btnText = document.getElementById('btn-text');
        const btnLoading = document.getElementById('btn-loading');
        const previewMapping = [
            {inputId: 'event_name', targetId: 'preview-event-name', fallback: 'test'},
            {inputId: 'event_datetime', targetId: 'preview-event-datetime', fallback: '12-12-2026'},
            {inputId: 'event_location', targetId: 'preview-event-location', fallback: 'cocai'},
            {inputId: 'event_highlights', targetId: 'preview-event-highlights', fallback: 'Live DJ set, art installations, open bar'},
            {inputId: 'event_call_to_action', targetId: 'preview-event-cta', fallback: 'RSVP now for early bird perks'},
        ];
            
        const resetGenerateButton = () => {
            if (!generateBtn || !btnText || !btnLoading) {
                return;
            }

            generateBtn.disabled = false;
            btnLoading.classList.add('hidden');
            btnText.classList.remove('hidden');
        };

        const scheduleButtonReset = () => {
            const restoreState = () => resetGenerateButton();

            window.addEventListener('focus', restoreState, { once: true });
            setTimeout(restoreState, 9000);
        };

        const syncPreview = ({ inputId, targetId, fallback }) => {
            const inputEl = document.getElementById(inputId);
            const targetEl = document.getElementById(targetId);

            if (!targetEl) {
                return;
            }

            const updateValue = () => {
                const current = inputEl?.value?.trim();
                targetEl.textContent = current || fallback;
            };

            updateValue();
            inputEl?.addEventListener('input', updateValue);
        };

        if (reelForm && generateBtn && btnText && btnLoading) {
            reelForm.addEventListener('submit', function(e) {
                generateBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');

                scheduleButtonReset();
        });

            previewMapping.forEach(syncPreview);
        }
    </script>
</body>
</html>

