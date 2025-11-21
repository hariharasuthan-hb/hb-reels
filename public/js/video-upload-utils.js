/**
 * Reusable Video Upload Utility
 * Handles chunked uploads, compression, and progress tracking
 * Can be used by both member workout videos and admin demo videos
 */

class VideoUploadUtils {
    constructor(options = {}) {
        this.chunkSize = options.chunkSize || 5 * 1024 * 1024; // 5MB chunks
        this.compressionThreshold = options.compressionThreshold || 20 * 1024 * 1024; // 20MB
        this.chunkedThreshold = options.chunkedThreshold || 10 * 1024 * 1024; // 10MB
        this.maxRetries = options.maxRetries || 3;
        this.retryDelay = options.retryDelay || 1000;
    }

    /**
     * Compress video blob
     */
    async compressVideo(file) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            video.src = URL.createObjectURL(file);
            video.muted = true;
            video.playsInline = true;

            video.onloadedmetadata = () => {
                video.currentTime = 0;
            };

            video.onseeked = () => {
                const canvas = document.createElement('canvas');
                canvas.width = Math.min(video.videoWidth, 1280);
                canvas.height = Math.min(video.videoHeight, 720);
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((compressedBlob) => {
                    URL.revokeObjectURL(video.src);
                    resolve(compressedBlob || file);
                }, 'video/webm', 0.6); // 60% quality
            };

            video.onerror = () => resolve(file); // Fallback to original
        });
    }

    /**
     * Direct upload for smaller files with progress tracking
     */
    async uploadDirect(blob, fileName, uploadUrl, csrfToken, onProgress, additionalData = {}) {
        const formData = new FormData();
        // Use 'video' as default, but allow override via additionalData
        const videoFieldName = additionalData._videoFieldName || 'video';
        formData.append(videoFieldName, blob, fileName);
        
        // Add any additional form data (excluding internal flags)
        Object.keys(additionalData).forEach(key => {
            if (key !== '_videoFieldName') {
                formData.append(key, additionalData[key]);
            }
        });

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && onProgress) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    onProgress(percentComplete, e.loaded, e.total);
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message || 'Upload failed'));
                        }
                    } catch (e) {
                        // Try to extract error message from HTML response
                        const errorMatch = xhr.responseText.match(/<title>(.*?)<\/title>/i) || 
                                          xhr.responseText.match(/<h1>(.*?)<\/h1>/i);
                        const errorMsg = errorMatch ? errorMatch[1] : 'Invalid response from server';
                        reject(new Error(errorMsg));
                    }
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        reject(new Error(response.message || 'Upload failed with status: ' + xhr.status));
                    } catch (e) {
                        reject(new Error('Upload failed with status: ' + xhr.status));
                    }
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Network error during upload'));
            });

            xhr.open('POST', uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.send(formData);
        });
    }

    /**
     * Chunked upload for larger files
     */
    async uploadChunked(blob, fileName, uploadUrl, csrfToken, onProgress, additionalData = {}) {
        const chunkSize = this.chunkSize;
        const totalChunks = Math.ceil(blob.size / chunkSize);
        const uploadId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

        let uploadedBytes = 0;

        for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
            const start = chunkIndex * chunkSize;
            const end = Math.min(start + chunkSize, blob.size);
            const chunk = blob.slice(start, end);

            const formData = new FormData();
            formData.append('video_chunk', chunk);
            formData.append('chunk_index', chunkIndex);
            formData.append('total_chunks', totalChunks);
            formData.append('upload_id', uploadId);
            formData.append('file_name', fileName);
            formData.append('file_size', blob.size);
            
            // Add any additional form data
            Object.keys(additionalData).forEach(key => {
                formData.append(key, additionalData[key]);
            });

            let retries = 0;
            let success = false;

            while (retries < this.maxRetries && !success) {
                try {
                    const response = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`Chunk ${chunkIndex + 1} upload failed: ${response.statusText}`);
                    }

                    uploadedBytes += chunk.size;
                    const progress = (uploadedBytes / blob.size) * 100;
                    
                    if (onProgress) {
                        onProgress(progress, uploadedBytes, blob.size);
                    }

                    // Last chunk - finalize upload
                    if (chunkIndex === totalChunks - 1) {
                        try {
                            const data = await response.json();
                            if (!data.success) {
                                throw new Error(data.message || 'Upload finalization failed');
                            }
                            return data;
                        } catch (e) {
                            // If response is not JSON, try to get error message
                            const text = await response.text();
                            throw new Error(text || 'Upload finalization failed');
                        }
                    }

                    success = true;
                } catch (error) {
                    retries++;
                    if (retries >= this.maxRetries) {
                        throw new Error(`Failed to upload chunk ${chunkIndex + 1} after ${this.maxRetries} retries: ${error.message}`);
                    }
                    // Wait before retry
                    await new Promise(resolve => setTimeout(resolve, this.retryDelay * retries));
                }
            }
        }
    }

    /**
     * Main upload method - handles compression and routing to direct/chunked upload
     */
    async upload(file, uploadUrl, chunkedUploadUrl, csrfToken, options = {}) {
        const {
            onProgress = null,
            onCompressStart = null,
            onCompressEnd = null,
            compress = true,
            additionalData = {},
        } = options;

        let videoBlob = file;

        // Compress video if larger than threshold
        if (compress && file.size > this.compressionThreshold) {
            if (onCompressStart) onCompressStart();
            videoBlob = await this.compressVideo(file);
            if (onCompressEnd) onCompressEnd();
        }

        // Use chunked upload for files larger than threshold
        if (videoBlob.size > this.chunkedThreshold && chunkedUploadUrl) {
            return await this.uploadChunked(videoBlob, file.name, chunkedUploadUrl, csrfToken, onProgress, additionalData);
        } else {
            return await this.uploadDirect(videoBlob, file.name, uploadUrl, csrfToken, onProgress, additionalData);
        }
    }

    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }

    /**
     * Validate video file
     */
    validateFile(file, maxSize = 100 * 1024 * 1024, allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime']) {
        if (!file) {
            return { valid: false, error: 'No file selected' };
        }

        if (!allowedTypes.includes(file.type)) {
            return { valid: false, error: 'Invalid file type. Please select MP4, WebM, or MOV file.' };
        }

        if (file.size > maxSize) {
            return { valid: false, error: `File size exceeds ${this.formatFileSize(maxSize)}. Please compress your video or select a smaller file.` };
        }

        return { valid: true };
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VideoUploadUtils;
}

