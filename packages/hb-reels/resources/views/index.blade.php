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

                {{-- AI Content Description --}}
                <div class="mb-6">
                    <section class="space-y-4 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <div class="flex-1">
                                <h2 class="text-lg font-semibold text-gray-900 mb-1">Your Message</h2>
                                <p class="text-sm text-gray-600 mb-4">Describe your event, announcement, acknowledgement, or any message. Our AI will understand the context and extract key details to create an engaging video reel.</p>
                            </div>
                        </div>
                    <textarea 
                        id="event_text" 
                        name="event_text" 
                            rows="8"
                            class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none bg-white"
                            placeholder="Examples:
• Event: Join us for Summer Sunset Party on Friday, Nov 21 at 7:00 PM at Rooftop Bar, Downtown. Enjoy live DJ sets and open bar!
• Announcement: All schools in Karur district will remain closed on Monday, Nov 25 due to heavy rainfall. Stay safe!
• Acknowledgement: Congratulations to Team Phoenix for winning the Innovation Award 2025! Your hard work and dedication inspire us all.">{{ old('event_text') }}</textarea>
                        <div class="flex items-center gap-2 text-xs text-blue-700">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span>AI intelligently extracts key information based on your content type (events, announcements, acknowledgements, etc.)</span>
                        </div>
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

        if (reelForm && generateBtn && btnText && btnLoading) {
            reelForm.addEventListener('submit', function(e) {
                generateBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');

                scheduleButtonReset();
        });
        }
    </script>
</body>
</html>

