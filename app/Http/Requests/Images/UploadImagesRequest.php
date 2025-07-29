<?php

declare(strict_types=1);

namespace App\Http\Requests\Images;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagesRequest extends FormRequest
{
    const RELATED_ID = 'relatedId';
    const IMAGES = 'images';
    const TYPE = 'type';

    public function rules(): array
    {
        return [
            self::IMAGES => 'required|array',
            self::IMAGES . '.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            self::TYPE => 'required|string',
        ];
    }

    public function validationData(): array
    {
        return array_merge($this->all(), [
            self::RELATED_ID => $this->route(self::RELATED_ID),
        ]);
    }

    public function getRelatedId(): int
    {
        return (int) $this->route(self::RELATED_ID);
    }

    public function getImages(): array
    {
        return $this->file(self::IMAGES) ?? [];
    }

    public function getType(): string
    {
        return (string) $this->input(self::TYPE);
    }
}
