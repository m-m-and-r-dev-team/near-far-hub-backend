<?php

declare(strict_types=1);

namespace App\Services\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;
use Illuminate\Support\Facades\Log;

class AwsImageUploadService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    private const THUMBNAIL_SIZES = [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 800, 'height' => 600],
    ];

    private ImageManager $imageManager;
    private string $disk;
    private string $baseUrl;

    public function __construct()
    {
        // Fixed for Intervention Image v3.x
        $this->imageManager = new ImageManager(new Driver());
        // Alternative: $this->imageManager = new ImageManager('gd');

        $this->disk = config('filesystems.default', 's3');
        $this->baseUrl = config('filesystems.disks.s3.url', '');
    }

    /**
     * Upload and process image for a model
     */
    public function uploadForModel(
        UploadedFile $file,
                     $model,
        ImageTypeEnum $type,
        array $options = []
    ): Image {
        $this->validateFile($file);

        $filename = $this->generateFilename($file, $type);
        $path = $this->generatePath($type, $model->getId() ?? 'temp');

        try {
            // Process the image
            $processedImage = $this->processImage($file, $options);

            // Upload to S3
            $fullPath = $path . '/' . $filename;
            $uploaded = Storage::disk($this->disk)->put($fullPath, $processedImage);

            if (!$uploaded) {
                throw new Exception('Failed to upload image to storage');
            }

            // Get image dimensions
            $imageData = getimagesizefromstring($processedImage);
            $width = $imageData[0] ?? null;
            $height = $imageData[1] ?? null;

            // Create image record
            $imageRecord = new Image([
                Image::TYPE => $type,
                Image::FILENAME => $filename,
                Image::ORIGINAL_NAME => $file->getClientOriginalName(),
                Image::PATH => $fullPath,
                Image::URL => $this->getUrl($fullPath),
                Image::SIZE => strlen($processedImage),
                Image::MIME_TYPE => $file->getMimeType(),
                Image::WIDTH => $width,
                Image::HEIGHT => $height,
                Image::ALT_TEXT => $options['alt_text'] ?? null,
                Image::SORT_ORDER => $options['sort_order'] ?? 0,
                Image::IS_PRIMARY => $options['is_primary'] ?? false,
                Image::IS_ACTIVE => true,
                Image::METADATA => $this->generateMetadata($file, $options),
            ]);

            // Associate with model
            $model->imagesRelation()->save($imageRecord);

            // Generate thumbnails if requested
            if ($options['generate_thumbnails'] ?? true) {
                $this->generateThumbnails($file, $path, $filename, $imageRecord);
            }

            return $imageRecord->fresh();

        } catch (Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'type' => $type->value,
                'model' => get_class($model),
                'model_id' => $model->getId() ?? null
            ]);
            throw $e;
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultipleForModel(
        array $files,
              $model,
        ImageTypeEnum $type,
        array $options = []
    ): array {
        $uploadedImages = [];
        $isPrimarySet = false;

        foreach ($files as $index => $file) {
            $fileOptions = $options;
            $fileOptions['sort_order'] = $index;

            // Set first image as primary if not specified
            if (!$isPrimarySet && ($options['auto_set_primary'] ?? true)) {
                $fileOptions['is_primary'] = true;
                $isPrimarySet = true;
            }

            try {
                $uploadedImages[] = $this->uploadForModel($file, $model, $type, $fileOptions);
            } catch (Exception $e) {
                Log::warning('Failed to upload image in batch', [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
                // Continue with other files
            }
        }

        return $uploadedImages;
    }

    /**
     * Delete image and its files
     */
    public function deleteImage(Image $image): bool
    {
        try {
            // Delete main image
            Storage::disk($this->disk)->delete($image->getPath());

            // Delete thumbnails
            $this->deleteThumbnails($image);

            // Delete database record
            $image->delete();

            return true;

        } catch (Exception $e) {
            Log::error('Failed to delete image', [
                'image_id' => $image->getId(),
                'path' => $image->getPath(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update image metadata
     */
    public function updateImage(Image $image, array $data): Image
    {
        $allowedFields = [
            Image::ALT_TEXT,
            Image::SORT_ORDER,
            Image::IS_PRIMARY,
            Image::IS_ACTIVE,
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        // If setting as primary, unset other primary images
        if (isset($updateData[Image::IS_PRIMARY]) && $updateData[Image::IS_PRIMARY]) {
            $this->unsetOtherPrimaryImages($image);
        }

        $image->update($updateData);
        return $image->fresh();
    }

    /**
     * Reorder images
     */
    public function reorderImages($model, array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            $model->imagesRelation()
                ->where('id', $imageId)
                ->update(['sort_order' => $index]);
        }
    }

    /**
     * Get optimized image URL with transformations
     */
    public function getOptimizedUrl(Image $image, array $transformations = []): string
    {
        $baseUrl = $image->getUrl();

        // Add transformations to URL if using a CDN that supports it
        if (!empty($transformations) && config('images.cdn_transformations', false)) {
            $params = http_build_query($transformations);
            return $baseUrl . '?' . $params;
        }

        return $baseUrl;
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(Image $image, string $size = 'medium'): string
    {
        $pathInfo = pathinfo($image->getPath());
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];

        return $this->getUrl($thumbnailPath);
    }

    // Private helper methods

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new Exception('File type not allowed');
        }
    }

    private function generateFilename(UploadedFile $file, ImageTypeEnum $type): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = hash('sha256', $file->getContent() . time());

        return $type->value . '_' . Str::random(8) . '_' . substr($hash, 0, 16) . '.' . $extension;
    }

    private function generatePath(ImageTypeEnum $type, $modelId): string
    {
        $year = date('Y');
        $month = date('m');

        return "images/{$type->value}/{$year}/{$month}/{$modelId}";
    }

    private function processImage(UploadedFile $file, array $options = []): string
    {
        // Updated for Intervention Image v3.x
        $image = $this->imageManager->read($file->getContent());

        // Apply quality settings
        $quality = $options['quality'] ?? 85;

        // Auto-orient based on EXIF data (if needed, check v3.x documentation)
        // $image->orientate(); // This method might be different in v3.x

        // Resize if max dimensions specified
        if (isset($options['max_width']) || isset($options['max_height'])) {
            $image->scale(
                width: $options['max_width'] ?? null,
                height: $options['max_height'] ?? null
            );
        }

        // Apply watermark if specified
        if ($options['watermark'] ?? false) {
            $this->applyWatermark($image, $options);
        }

        // Updated encoding for v3.x
        return $image->encodeByMediaType(quality: $quality);
    }

    private function generateThumbnails(UploadedFile $file, string $basePath, string $filename, Image $imageRecord): void
    {
        $image = $this->imageManager->read($file->getContent());
        $pathInfo = pathinfo($filename);
        $baseFilename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        foreach (self::THUMBNAIL_SIZES as $sizeName => $dimensions) {
            try {
                $thumbnail = clone $image;

                // Updated for v3.x - cover method for fitting
                $thumbnail->cover($dimensions['width'], $dimensions['height']);

                $thumbnailFilename = $baseFilename . '_' . $sizeName . '.' . $extension;
                $thumbnailPath = $basePath . '/thumbnails/' . $thumbnailFilename;

                $encodedThumbnail = $thumbnail->encodeByMediaType(quality: 80);
                Storage::disk($this->disk)->put($thumbnailPath, $encodedThumbnail);

                // Update metadata with thumbnail info
                $metadata = $imageRecord->getMetadata() ?? [];
                $metadata['thumbnails'][$sizeName] = [
                    'path' => $thumbnailPath,
                    'url' => $this->getUrl($thumbnailPath),
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height']
                ];
                $imageRecord->update([Image::METADATA => $metadata]);

            } catch (Exception $e) {
                Log::warning('Failed to generate thumbnail', [
                    'size' => $sizeName,
                    'image_id' => $imageRecord->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function deleteThumbnails(Image $image): void
    {
        $metadata = $image->getMetadata();

        if (isset($metadata['thumbnails'])) {
            foreach ($metadata['thumbnails'] as $thumbnail) {
                try {
                    Storage::disk($this->disk)->delete($thumbnail['path']);
                } catch (Exception $e) {
                    Log::warning('Failed to delete thumbnail', [
                        'path' => $thumbnail['path'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    private function unsetOtherPrimaryImages(Image $image): void
    {
        $image->imageable->imagesRelation()
            ->where('id', '!=', $image->getId())
            ->update(['is_primary' => false]);
    }

    private function generateMetadata(UploadedFile $file, array $options): array
    {
        return [
            'original_filename' => $file->getClientOriginalName(),
            'upload_time' => now()->toISOString(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'options_used' => $options,
            'thumbnails' => [], // Will be populated by generateThumbnails
        ];
    }

    private function applyWatermark($image, array $options): void
    {
        // Updated for Intervention Image v3.x
        $watermarkPath = config('images.watermark_path');

        if ($watermarkPath && file_exists($watermarkPath)) {
            $watermark = $this->imageManager->read($watermarkPath);
            // Updated method for v3.x - check documentation for exact syntax
            $image->place($watermark, 'bottom-right', 10, 10);
        }
    }

    private function getUrl(string $path): string
    {
        if ($this->disk === 's3') {
            return Storage::disk($this->disk)->url($path);
        }

        return Storage::disk($this->disk)->url($path);
    }
}