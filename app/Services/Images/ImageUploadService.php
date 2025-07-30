<?php

declare(strict_types=1);

namespace App\Services\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use Exception;
use Illuminate\Support\Collection as IlluminateSupportCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUploadService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload multiple images for a model
     * @param UploadedFile[] $files
     * @param Model $model
     * @param ImageTypeEnum $type
     * @param array $options
     * @return IlluminateSupportCollection
     * @throws Exception
     */
    public function uploadMultipleForModel(
        array         $files,
        Model         $model,
        ImageTypeEnum $type,
        array         $options = []
    ): IlluminateSupportCollection
    {
        $uploadedImages = collect();
        $isPrimaryRequired = $options['auto_set_primary'] ?? !$model->imagesRelation()->where(Image::IS_PRIMARY, true)->exists();

        foreach ($files as $index => $file) {
            try {
                $image = $this->uploadSingleForModel($file, $model, $type, array_merge($options, [
                    Image::IS_PRIMARY => $isPrimaryRequired && $index === 0,
                    'sort_order' => $options['sort_order'] ?? ($model->imagesRelation()->count() + $index),
                ]));

                $uploadedImages->push($image);
            } catch (Exception $e) {
                Log::error("Failed to upload image {$file->getClientOriginalName()}: " . $e->getMessage());

                if ($isPrimaryRequired && $index === 0 && $files->count() > 1) {
                    $isPrimaryRequired = true;
                }

                throw $e;
            }
        }

        return $uploadedImages;
    }

    /**
     * Upload a single image for a model
     * @throws Exception
     */
    public function uploadSingleForModel(
        UploadedFile  $file,
        Model         $model,
        ImageTypeEnum $type,
        array         $options = []
    ): Image
    {
        $this->validateImage($file);

        $filename = $this->generateFilename($file);
        $path = $type->value . '/' . $filename;

        try {
            $processedImage = $this->processImage($file, $options);

            Storage::disk('s3')->put($path, $processedImage['content']);

            $metadata = [];
            if ($options['generate_thumbnails'] ?? false) {
                $metadata['thumbnails'] = $this->generateThumbnails($file, $type, $filename, $options);
            }

            return $model->imagesRelation()->create([
                Image::IMAGE_LINK => $filename,
                Image::TYPE => $type->value,
                Image::ALT_TEXT => $options['alt_text'] ?? null,
                Image::SORT_ORDER => $options['sort_order'] ?? 0,
                Image::IS_PRIMARY => $options['is_primary'] ?? false,
                Image::IS_ACTIVE => $options['is_active'] ?? true,
                Image::METADATA => $metadata,
                Image::WIDTH => $processedImage['width'],
                Image::HEIGHT => $processedImage['height'],
                Image::FILE_SIZE => $processedImage['file_size'],
            ]);
        } catch (Exception $e) {
            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
            }
            throw new Exception("Failed to process image: " . $e->getMessage());
        }
    }

    /**
     * Update image details
     */
    public function updateImage(Image $image, array $data): Image
    {
        $updateData = array_filter([
            Image::ALT_TEXT => $data['alt_text'] ?? null,
            Image::SORT_ORDER => $data['sort_order'] ?? null,
            Image::IS_ACTIVE => $data['is_active'] ?? null,
        ], fn($value) => $value !== null);

        if (isset($data['is_primary']) && $data['is_primary']) {
            $image->imageable->imagesRelation()
                ->where('type', $image->getType())
                ->where('id', '!=', $image->getId())
                ->update([Image::IS_PRIMARY => false]);

            $updateData[Image::IS_PRIMARY] = true;
        }

        $image->update($updateData);
        return $image->fresh();
    }

    /**
     * Delete an image
     */
    public function deleteImage(Image $image): bool
    {
        $path = $image->getFullPath();

        try {
            Storage::disk('s3')->delete($path);

            $metadata = $image->getMetadata();
            if (isset($metadata['thumbnails'])) {
                foreach ($metadata['thumbnails'] as $thumbnail) {
                    if (isset($thumbnail['path'])) {
                        Storage::disk('s3')->delete($thumbnail['path']);
                    }
                }
            }

            if ($image->getIsPrimary()) {
                $nextImage = $image->imageable->imagesRelation()
                    ->where('type', $image->getType())
                    ->where('id', '!=', $image->getId())
                    ->orderBy('sort_order')
                    ->first();

                if ($nextImage) {
                    $nextImage->update([Image::IS_PRIMARY => true]);
                }
            }

            $image->delete();
            return true;
        } catch (Exception $e) {
            Log::error("Failed to delete image {$image->getId()}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reorder images
     */
    public function reorderImages(Model $model, array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            $model->imagesRelation()
                ->where('id', $imageId)
                ->update([Image::SORT_ORDER => $index]);
        }
    }

    /**
     * Set primary image
     */
    public function setPrimaryImage(Image $image): Image
    {
        $image->imageable->imagesRelation()
            ->where('type', $image->getType())
            ->where('id', '!=', $image->getId())
            ->update([Image::IS_PRIMARY => false]);

        $image->update([Image::IS_PRIMARY => true]);

        return $image->fresh();
    }

    /**
     * @throws Exception
     */
    private function validateImage(UploadedFile $file): void
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        if ($file->getSize() > $maxSize) {
            throw new Exception('Image file size cannot exceed 10MB');
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('Invalid image format. Only JPEG, PNG, GIF, and WebP are allowed');
        }

        if (!getimagesize($file->getPathname())) {
            throw new Exception('File is not a valid image');
        }
    }

    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . strtolower($extension);
    }

    private function processImage(UploadedFile $file, array $options): array
    {
        $quality = $options['quality'] ?? 85;
        $maxWidth = $options['max_width'] ?? 1200;
        $maxHeight = $options['max_height'] ?? 900;

        try {
            $image = $this->imageManager->read($file->getPathname());

            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scaleDown($maxWidth, $maxHeight);
            }

            $mimeType = $file->getMimeType();
            $encoded = $this->encodeImageByMimeType($image, $mimeType, $quality);

            return [
                'content' => (string)$encoded,
                'width' => $image->width(),
                'height' => $image->height(),
                'file_size' => strlen((string)$encoded),
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to process image: " . $e->getMessage());
        }
    }

    private function encodeImageByMimeType($image, string $mimeType, int $quality)
    {
        return match ($mimeType) {
            'image/png' => $image->toPng(),
            'image/gif' => $image->toGif(),
            'image/webp' => $image->toWebp($quality),
            default => $image->toJpeg($quality),
        };
    }

    private function generateThumbnails(UploadedFile $file, ImageTypeEnum $type, string $filename, array $options): array
    {
        $thumbnails = [];
        $sizes = [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
            'large' => ['width' => 600, 'height' => 600],
        ];

        try {
            $image = $this->imageManager->read($file->getPathname());
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $mimeType = $file->getMimeType();

            foreach ($sizes as $sizeName => $dimensions) {
                try {
                    $thumbnailFilename = $baseName . '_' . $sizeName . '.' . $extension;
                    $thumbnailPath = $type->value . '/thumbnails/' . $thumbnailFilename;

                    $thumbnail = clone $image;
                    $thumbnail->scaleDown($dimensions['width'], $dimensions['height']);

                    $encoded = $mimeType === 'image/png' ?
                        $thumbnail->toPng() :
                        $thumbnail->toJpeg(80);

                    Storage::disk('s3')->put($thumbnailPath, (string)$encoded);

                    $thumbnails[$sizeName] = [
                        'filename' => $thumbnailFilename,
                        'path' => $thumbnailPath,
                        'url' => Storage::disk('s3')->url($thumbnailPath),
                        'width' => $thumbnail->width(),
                        'height' => $thumbnail->height(),
                    ];
                } catch (Exception $e) {
                    Log::warning("Failed to generate {$sizeName} thumbnail for {$filename}: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            Log::error("Failed to generate thumbnails for {$filename}: " . $e->getMessage());
        }

        return $thumbnails;
    }
}