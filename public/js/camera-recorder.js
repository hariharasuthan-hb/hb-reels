/**
 * Reusable Camera Recorder Utility
 * Handles camera detection, recording, and fallback to file upload
 */
class CameraRecorder {
    constructor(options = {}) {
        this.maxDuration = options.maxDuration || 60; // seconds
        this.videoConstraints = options.videoConstraints || { facingMode: 'user' };
        this.audioEnabled = options.audioEnabled !== false;
        this.onCameraAvailable = options.onCameraAvailable || null;
        this.onCameraUnavailable = options.onCameraUnavailable || null;
        this.onRecordingStart = options.onRecordingStart || null;
        this.onRecordingStop = options.onRecordingStop || null;
        this.onError = options.onError || null;
        
        this.mediaRecorder = null;
        this.stream = null;
        this.recordedBlobs = [];
        this.recordingTimer = null;
        this.recordingSeconds = 0;
        this.isRecording = false;
    }

    /**
     * Check if camera is available
     */
    async checkCameraAvailability() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                return false;
            }

            // Try to enumerate devices to check if camera exists
            const devices = await navigator.mediaDevices.enumerateDevices();
            const hasVideoInput = devices.some(device => device.kind === 'videoinput');
            
            if (!hasVideoInput) {
                return false;
            }

            // Try to get user media to verify camera access
            const testStream = await navigator.mediaDevices.getUserMedia({
                video: this.videoConstraints,
                audio: this.audioEnabled
            });
            
            // Stop the test stream immediately
            testStream.getTracks().forEach(track => track.stop());
            
            return true;
        } catch (error) {
            console.warn('Camera not available:', error);
            return false;
        }
    }

    /**
     * Initialize camera and start preview
     */
    async initializeCamera(videoElement) {
        try {
            if (!videoElement) {
                throw new Error('Video element is required');
            }

            const constraints = {
                video: this.videoConstraints,
                audio: this.audioEnabled
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElement.srcObject = this.stream;
            
            // Ensure video plays
            try {
                await videoElement.play();
            } catch (playError) {
                console.warn('Video autoplay failed, but stream is active:', playError);
            }
            
            if (this.onCameraAvailable) {
                this.onCameraAvailable();
            }

            return true;
        } catch (error) {
            console.error('Error initializing camera:', error);
            
            if (this.onCameraUnavailable) {
                this.onCameraUnavailable(error);
            }
            
            if (this.onError) {
                this.onError(error);
            }
            
            throw error;
        }
    }

    /**
     * Start recording
     */
    async startRecording(videoElement) {
        try {
            if (this.isRecording) {
                throw new Error('Recording already in progress');
            }

            // Initialize camera if not already done
            if (!this.stream) {
                await this.initializeCamera(videoElement);
            }

            // Check for supported MIME types
            const options = { mimeType: 'video/webm;codecs=vp9,opus' };
            if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                options.mimeType = 'video/webm;codecs=vp8,opus';
                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    options.mimeType = 'video/webm';
                }
            }

            this.mediaRecorder = new MediaRecorder(this.stream, options);
            this.recordedBlobs = [];
            this.recordingSeconds = 0;
            this.isRecording = true;

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    this.recordedBlobs.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                this.isRecording = false;
                if (this.recordingTimer) {
                    clearInterval(this.recordingTimer);
                    this.recordingTimer = null;
                }
            };

            // Start timer
            this.recordingTimer = setInterval(() => {
                this.recordingSeconds++;
                
                if (this.recordingSeconds >= this.maxDuration) {
                    this.stopRecording();
                }
            }, 1000);

            // Start recording
            this.mediaRecorder.start(1000); // Collect data every second
            
            console.log('MediaRecorder started, state:', this.mediaRecorder.state);

            if (this.onRecordingStart) {
                this.onRecordingStart();
            }

            return true;
        } catch (error) {
            console.error('Error starting recording:', error);
            this.isRecording = false;
            
            if (this.onError) {
                this.onError(error);
            }
            
            throw error;
        }
    }

    /**
     * Stop recording
     */
    stopRecording() {
        if (!this.isRecording || !this.mediaRecorder) {
            return null;
        }

        if (this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
        }

        if (this.recordingTimer) {
            clearInterval(this.recordingTimer);
            this.recordingTimer = null;
        }

        this.isRecording = false;

        // Create blob from recorded chunks
        const blob = new Blob(this.recordedBlobs, { type: 'video/webm' });

        if (this.onRecordingStop) {
            this.onRecordingStop(blob, this.recordingSeconds);
        }

        return blob;
    }

    /**
     * Stop camera stream
     */
    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        if (this.recordingTimer) {
            clearInterval(this.recordingTimer);
            this.recordingTimer = null;
        }

        this.isRecording = false;
    }

    /**
     * Get recording duration in seconds
     */
    getRecordingDuration() {
        return this.recordingSeconds;
    }

    /**
     * Format duration as MM:SS
     */
    formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Cleanup resources
     */
    cleanup() {
        this.stopCamera();
        this.recordedBlobs = [];
        this.mediaRecorder = null;
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CameraRecorder;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.CameraRecorder = CameraRecorder;
}

