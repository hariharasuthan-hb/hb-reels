<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Upload an image file
     */
    public function upload(UploadedFile $file, string $folder = 'cms', ?string $oldImage = null): string
    {
        // Delete old image if exists
        if ($oldImage) {
            $this->delete($oldImage);
        }

        // Generate unique filename
        $filename = $this->generateFilename($file);

        // Store the file
        $path = $file->storeAs($folder, $filename, 'public');

        return $path;
    }

    /**
     * Delete an image
     */
    public function delete(?string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$name}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Get full URL for image
     */
    public function url(?string $path): ?string
    {
        return file_url($path);
    }

    /**
     * Validate image file
     */
    public function validate(UploadedFile $file, int $maxSize = 5120): bool
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSizeKB = $maxSize; // in KB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        if ($file->getSize() > ($maxSizeKB * 1024)) {
            return false;
        }

        return true;
    }
}

