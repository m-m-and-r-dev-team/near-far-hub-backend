<?php

declare(strict_types=1);

namespace App\Http\Controllers\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Images\DeleteImageRequest;
use App\Http\Requests\Images\GetImagesRequest;
use App\Http\Requests\Images\SetPrimaryImageRequest;
use App\Http\Requests\Images\UploadImagesRequest;
use App\Http\Resources\Images\ImageResource;
use App\Http\Resources\Images\ImageResourceCollection;
use App\Models\Images\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function getImages(GetImagesRequest $request): ImageResourceCollection
    {
        $id = $request->getRelatedId();

        $images = Image::where(Image::RELATED_ID, $id)
            ->where(Image::TYPE, $request->getType())
            ->orderBy(Image::IS_PRIMARY, 'desc')
            ->get();

        return new ImageResourceCollection($images);
    }

    public function uploadImages(UploadImagesRequest $request): ImageResourceCollection
    {
        $id = $request->getRelatedId();
        $newImages = [];

        $isPrimaryRequired = !Image::where(Image::RELATED_ID, $id)
            ->where(Image::TYPE, $request->getType())
            ->where(Image::IS_PRIMARY, true)
            ->exists();

        foreach ($request->getImages() as $index => $image) {
            $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $path = $request->getType() . '/' . $filename;

            Storage::disk('s3')->put($path, $image->get());

            $isPrimary = $isPrimaryRequired && $index === 0;

            $imageModel = Image::create([
                Image::RELATED_ID => $id,
                Image::TYPE => $request->getType(),
                Image::IMAGE_LINK => $filename,
                Image::IS_PRIMARY => $isPrimary
            ]);

            $newImages[] = $imageModel;
        }

        return new ImageResourceCollection(collect($newImages));
    }

    public function setPrimaryImage(SetPrimaryImageRequest $request): ImageResource
    {
        $imageId = $request->getImageId();
        $image = Image::findOrFail($imageId);

        Image::where(Image::RELATED_ID, $image->getRelatedId())
            ->where(Image::TYPE, $image->getType())
            ->update([Image::IS_PRIMARY => false]);

        $image->update([Image::IS_PRIMARY => true]);

        return new ImageResource($image);
    }

    public function deleteImage(DeleteImageRequest $request): JsonResponse
    {
        $imageId = $request->getImageId();
        $image = Image::findOrFail($imageId);
        $relatedId = $image->getRelatedId();
        $isPrimary = $image->getIsPrimary();

        $path = $image->getType() . '/' . $image->getImageLink();
        Storage::disk('s3')->delete($path);

        $image->delete();

        if ($isPrimary) {
            $nextImage = Image::where(Image::RELATED_ID, $relatedId)
                ->where(Image::TYPE, $image->getType())
                ->first();
            if ($nextImage) {
                $nextImage->update([Image::IS_PRIMARY => true]);
            }
        }

        return response()->json([
            'message' => 'Image deleted successfully'
        ], 204);
    }

    public function handleCategoryImage(int $categoryId, UploadedFile $imageFile): void
    {
        $existingImages = Image::where(Image::RELATED_ID, $categoryId)
            ->where(Image::TYPE, ImageTypeEnum::CATEGORY->value)
            ->get();

        foreach ($existingImages as $image) {
            $path = ImageTypeEnum::CATEGORY->value . '/' . $image->getImageLink();
            Storage::disk('s3')->delete($path);
            $image->delete();
        }

        $filename = Str::uuid() . '.' . $imageFile->getClientOriginalExtension();
        $path = ImageTypeEnum::CATEGORY->value . '/' . $filename;

        Storage::disk('s3')->put($path, $imageFile->get());

        Image::create([
            Image::RELATED_ID => $categoryId,
            Image::TYPE => ImageTypeEnum::CATEGORY->value,
            Image::IMAGE_LINK => $filename,
            Image::IS_PRIMARY => true
        ]);
    }

    public function handleSingleImageUpload(int $relatedId, UploadedFile $imageFile, string $type): void
    {
        $existingImages = Image::where(Image::RELATED_ID, $relatedId)
            ->where(Image::TYPE, $type)
            ->get();

        foreach ($existingImages as $image) {
            $path = $type . '/' . $image->getImageLink();
            Storage::disk('s3')->delete($path);
            $image->delete();
        }

        $filename = Str::uuid() . '.' . $imageFile->getClientOriginalExtension();
        $path = $type . '/' . $filename;

        Storage::disk('s3')->put($path, $imageFile->get());

        Image::create([
            Image::RELATED_ID => $relatedId,
            Image::TYPE => $type,
            Image::IMAGE_LINK => $filename,
            Image::IS_PRIMARY => true
        ]);
    }
}
