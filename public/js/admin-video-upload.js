/**
 * Optimized Video Upload for Admin Workout Plan Demo Videos
 * Uses reusable VideoUploadUtils class
 */

(function() {
    'use strict';
    
    // Initialize video upload utility
    const videoUploader = new VideoUploadUtils({
        chunkSize: 5 * 1024 * 1024, // 5MB chunks
        compressionThreshold: 20 * 1024 * 1024, // 20MB
        chunkedThreshold: 10 * 1024 * 1024, // 10MB
    });

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const demoVideoInput = document.getElementById('demo_video');
        if (!demoVideoInput) return;

        const form = demoVideoInput.closest('form');
        if (!form) return;

        // Create upload status container
        const statusContainer = document.createElement('div');
        statusContainer.id = 'demo-video-upload-status';
        statusContainer.className = 'mt-4 hidden';
        demoVideoInput.parentElement.appendChild(statusContainer);

        // Handle file selection
        demoVideoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file using utility
            const validation = videoUploader.validateFile(file, 100 * 1024 * 1024);
            if (!validation.valid) {
                showError(validation.error);
                return;
            }

            // Show upload option
            showUploadOption(file);
        });

        // Show upload option with preview
        function showUploadOption(file) {
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            statusContainer.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-semibold text-blue-900">Selected: ${file.name}</p>
                            <p class="text-xs text-blue-700">Size: ${fileSizeMB} MB</p>
                        </div>
                        <button type="button" id="start-upload-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Upload Video
                        </button>
                    </div>
                    <div id="upload-progress-container" class="hidden">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-blue-800" id="upload-status-text">Preparing upload...</span>
                            <span class="text-sm font-semibold text-blue-800" id="upload-progress-text">0%</span>
                        </div>
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div id="upload-progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            `;
            statusContainer.classList.remove('hidden');

            // Handle upload button click
            const uploadBtn = document.getElementById('start-upload-btn');
            if (uploadBtn) {
                uploadBtn.addEventListener('click', () => uploadVideo(file));
            }
        }

        // Upload video with optimization
        async function uploadVideo(file) {
            const uploadBtn = document.getElementById('start-upload-btn');
            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const progressText = document.getElementById('upload-progress-text');
            const statusText = document.getElementById('upload-status-text');

            if (uploadBtn) uploadBtn.disabled = true;
            if (progressContainer) progressContainer.classList.remove('hidden');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const uploadUrl = '/admin/workout-plans/upload-demo-video';
                const chunkedUploadUrl = '/admin/workout-plans/upload-demo-video-chunk';

                // Use reusable upload utility
                const response = await videoUploader.upload(
                    file,
                    uploadUrl,
                    chunkedUploadUrl,
                    csrfToken,
                    {
                        onProgress: (percent, loaded, total) => {
                            progressBar.style.width = percent + '%';
                            progressText.textContent = Math.round(percent) + '%';
                        },
                        onCompressStart: () => {
                            statusText.textContent = 'Compressing video...';
                        },
                        onCompressEnd: () => {
                            statusText.textContent = 'Uploading video...';
                        },
                        additionalData: {
                            _videoFieldName: 'demo_video' // Admin expects 'demo_video' field name
                        }
                    }
                );
                
                // Create hidden input with video path
                let videoPathInput = document.getElementById('demo_video_path');
                if (!videoPathInput) {
                    videoPathInput = document.createElement('input');
                    videoPathInput.type = 'hidden';
                    videoPathInput.id = 'demo_video_path';
                    videoPathInput.name = 'demo_video_path';
                    form.appendChild(videoPathInput);
                }
                videoPathInput.value = response.video_path;
                
                // Disable file input since we've uploaded via AJAX
                demoVideoInput.disabled = true;
                
                statusContainer.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center text-green-800">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold">Video uploaded successfully! You can now submit the form.</span>
                        </div>
                    </div>
                `;
            } catch (error) {
                showError('Upload failed: ' + error.message);
            } finally {
                if (uploadBtn) uploadBtn.disabled = false;
            }
        }


        // Show error message
        function showError(message) {
            statusContainer.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center text-red-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold">${message}</span>
                    </div>
                </div>
            `;
            statusContainer.classList.remove('hidden');
        }
    });
})();

