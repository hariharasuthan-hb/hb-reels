/**
 * Optimized Video Upload with Chunking and Progress Tracking
 * Handles large video files by splitting into chunks
 */

class VideoUploader {
    constructor(options = {}) {
        this.chunkSize = options.chunkSize || 5 * 1024 * 1024; // 5MB chunks
        this.maxRetries = options.maxRetries || 3;
        this.retryDelay = options.retryDelay || 1000;
    }

    /**
     * Upload video in chunks with progress tracking
     */
    async uploadChunked(file, uploadUrl, exerciseName, workoutPlanId, onProgress) {
        const totalChunks = Math.ceil(file.size / this.chunkSize);
        const uploadId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        
        let uploadedBytes = 0;
        
        try {
            // Upload chunks sequentially
            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const start = chunkIndex * this.chunkSize;
                const end = Math.min(start + this.chunkSize, file.size);
                const chunk = file.slice(start, end);
                
                const formData = new FormData();
                formData.append('video_chunk', chunk);
                formData.append('chunk_index', chunkIndex);
                formData.append('total_chunks', totalChunks);
                formData.append('upload_id', uploadId);
                formData.append('exercise_name', exerciseName);
                formData.append('file_name', file.name);
                formData.append('file_size', file.size);
                formData.append('duration_seconds', 60);
                
                let retries = 0;
                let success = false;
                
                while (retries < this.maxRetries && !success) {
                    try {
                        const response = await fetch(uploadUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: formData
                        });
                        
                        if (!response.ok) {
                            throw new Error(`Upload failed: ${response.statusText}`);
                        }
                        
                        const data = await response.json();
                        
                        if (data.success || chunkIndex === totalChunks - 1) {
                            success = true;
                            uploadedBytes += chunk.size;
                            
                            // Update progress
                            if (onProgress) {
                                const progress = (uploadedBytes / file.size) * 100;
                                onProgress(progress, uploadedBytes, file.size);
                            }
                            
                            // Last chunk - return final response
                            if (chunkIndex === totalChunks - 1) {
                                return data;
                            }
                        } else {
                            throw new Error('Chunk upload failed');
                        }
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
        } catch (error) {
            throw new Error(`Video upload failed: ${error.message}`);
        }
    }

    /**
     * Compress video before upload (client-side compression)
     */
    async compressVideo(videoBlob, maxSizeMB = 50) {
        return new Promise((resolve, reject) => {
            const video = document.createElement('video');
            video.src = URL.createObjectURL(videoBlob);
            video.onloadedmetadata = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Set canvas dimensions (reduce if needed)
                const maxWidth = 1280;
                const maxHeight = 720;
                let width = video.videoWidth;
                let height = video.videoHeight;
                
                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width = width * ratio;
                    height = height * ratio;
                }
                
                canvas.width = width;
                canvas.height = height;
                
                video.oncanplay = () => {
                    ctx.drawImage(video, 0, 0, width, height);
                    
                    canvas.toBlob((blob) => {
                        URL.revokeObjectURL(video.src);
                        
                        // If still too large, try lower quality
                        if (blob.size > maxSizeMB * 1024 * 1024) {
                            canvas.toBlob((compressedBlob) => {
                                resolve(compressedBlob || blob);
                            }, 'video/webm', 0.5); // Lower quality
                        } else {
                            resolve(blob);
                        }
                    }, 'video/webm', 0.7); // 70% quality
                };
            };
            
            video.onerror = () => reject(new Error('Failed to load video'));
        });
    }
}

// Global uploader instance
window.videoUploader = new VideoUploader();

