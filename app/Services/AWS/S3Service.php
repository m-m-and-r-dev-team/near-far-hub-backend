<?php

declare(strict_types=1);

namespace App\Services\AWS;

use App\Enums\Images\ImageTypeEnum;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class S3Service
{
    private string $disk;
    private string $bucket;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'local');
        $this->bucket = config('filesystems.disks.s3.bucket', '');
    }

    /**
     * Upload file to S3 or local storage
     *
     * @throws Exception
     */
    public function uploadFile(
        UploadedFile $file,
        ImageTypeEnum $imageType,
        ?int $entityId = null,
        bool $makePublic = true
    ): array {
        $this->validateFile($file, $imageType);

        $fileName = $this->generateFileName($file, $imageType, $entityId);
        $filePath = $imageType->getStoragePath() . '/' . $fileName;

        try {
            // Upload original file
            $uploadedPath = Storage::disk($this->disk)->putFileAs(
                $imageType->getStoragePath(),
                $file,
                $fileName,
                $makePublic ? 'public' : 'private'
            );

            if (!$uploadedPath) {
                throw new Exception('Failed to upload file');
            }

            $fileData = [
                'original_name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $uploadedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'url' => $this->getFileUrl($uploadedPath),
            ];

            // Get image dimensions if it's an image
            if ($this->isImage($file)) {
                $dimensions = $this->getImageDimensions($file);
                $fileData['width'] = $dimensions['width'];
                $fileData['height'] = $dimensions['height'];

                // Generate thumbnails for images if needed
                if ($imageType->requiresImageDimensions()) {
                    $fileData['thumbnails'] = $this->generateThumbnails($file, $uploadedPath, $imageType);
                }
            }

            return $fileData;

        } catch (Exception $e) {
            // Clean up if upload failed
            if (isset($uploadedPath) && Storage::disk($this->disk)->exists($uploadedPath)) {
                Storage::disk($this->disk)->delete($uploadedPath);
            }
            throw new Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     *
     * @param UploadedFile[] $files
     * @throws Exception
     */
    public function uploadMultipleFiles(
        array $files,
        ImageTypeEnum $imageType,
        ?int $entityId = null,
        bool $makePublic = true
    ): array {
        $maxFiles = $imageType->getMaxImagesPerEntity();

        if (count($files) > $maxFiles) {
            throw new Exception("Maximum {$maxFiles} files allowed for {$imageType->getLabel()}");
        }

        $uploadedFiles = [];
        $uploadedPaths = [];

        try {
            foreach ($files as $index => $file) {
                $fileData = $this->uploadFile($file, $imageType, $entityId, $makePublic);
                $fileData['sort_order'] = $index + 1;
                $fileData['is_primary'] = $index === 0; // First file is primary

                $uploadedFiles[] = $fileData;
                $uploadedPaths[] = $fileData['file_path'];
            }

            return $uploadedFiles;

        } catch (Exception $e) {
            // Clean up all uploaded files if any upload failed
            foreach ($uploadedPaths as $path) {
                if (Storage::disk($this->disk)->exists($path)) {
                    Storage::disk($this->disk)->delete($path);
                }
            }
            throw $e;
        }
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $filePath): bool
    {
        try {
            if (Storage::disk($this->disk)->exists($filePath)) {
                return Storage::disk($this->disk)->delete($filePath);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete multiple files
     */
    public function deleteMultipleFiles(array $filePaths): bool
    {
        try {
            $existingPaths = array_filter($filePaths, function ($path) {
                return Storage::disk($this->disk)->exists($path);
            });

            if (empty($existingPaths)) {
                return true;
            }

            return Storage::disk($this->disk)->delete($existingPaths);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $filePath): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->url($filePath);
        }

        return Storage::disk($this->disk)->url($filePath);
    }

    /**
     * Get temporary URL for private files
     */
    public function getTemporaryUrl(string $filePath, int $expirationMinutes = 60): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes($expirationMinutes));
        }

        // For local storage, return regular URL
        return $this->getFileUrl($filePath);
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $filePath): bool
    {
        return Storage::disk($this->disk)->exists($filePath);
    }

    /**
     * Get file size
     */
    public function getFileSize(string $filePath): int
    {
        return Storage::disk($this->disk)->size($filePath);
    }

    /**
     * Move file to different path
     */
    public function moveFile(string $fromPath, string $toPath): bool
    {
        try {
            return Storage::disk($this->disk)->move($fromPath, $toPath);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Copy file to different path
     */
    public function copyFile(string $fromPath, string $toPath): bool
    {
        try {
            return Storage::disk($this->disk)->copy($fromPath, $toPath);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate uploaded file
     *
     * @throws Exception
     */
    private function validateFile(UploadedFile $file, ImageTypeEnum $imageType): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > $imageType->getMaxFileSize()) {
            $maxSizeMB = round($imageType->getMaxFileSize() / 1024 / 1024, 2);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }

        // Check mime type
        if (!in_array($file->getMimeType(), $imageType->getAllowedMimeTypes())) {
            throw new Exception('File type not allowed');
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $imageType->getAllowedExtensions())) {
            throw new Exception('File extension not allowed');
        }
    }

    /**
     * Generate unique file name
     */
    private function generateFileName(UploadedFile $file, ImageTypeEnum $imageType, ?int $entityId = null): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        $prefix = $entityId ? "entity_{$entityId}_" : '';
        $typePrefix = strtolower(str_replace('_', '', $imageType->value));

        return "{$prefix}{$typePrefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if file is an image
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Get image dimensions
     */
    private function getImageDimensions(UploadedFile $file): array
    {
        try {
            $imageInfo = getimagesize($file->getRealPath());
            return [
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
            ];
        } catch (Exception $e) {
            return ['width' => null, 'height' => null];
        }
    }

    /**
     * Generate thumbnails for images
     */
    private function generateThumbnails(UploadedFile $file, string $originalPath, ImageTypeEnum $imageType): array
    {
        if (!$this->isImage($file)) {
            return [];
        }

        $thumbnails = [];
        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 400, 'height' => 300],
            'large' => ['width' => 800, 'height' => 600],
        ];

        foreach ($sizes as $sizeName => $dimensions) {
            try {
                $thumbnailPath = $this->generateThumbnailPath($originalPath, $sizeName);

                // Create thumbnail using Intervention Image (you'll need to install this package)
                // For now, we'll just store the paths - implement actual thumbnail generation as needed
                $thumbnails[$sizeName] = [
                    'path' => $thumbnailPath,
                    'url' => $this->getFileUrl($thumbnailPath),
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                ];
            } catch (Exception $e) {
                // If thumbnail generation fails, continue with others
                continue;
            }
        }

        return $thumbnails;
    }

    /**
     * Generate thumbnail file path
     */
    private function generateThumbnailPath(string $originalPath, string $sizeName): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . "_{$sizeName}." . $pathInfo['extension'];
    }

    /**
     * Get storage disk instance
     */
    public function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * Get current storage disk name
     */
    public function getDiskName(): string
    {
        return $this->disk;
    }

    /**
     * Check if using S3
     */
    public function isUsingS3(): bool
    {
        return $this->disk === 's3';
    }
}