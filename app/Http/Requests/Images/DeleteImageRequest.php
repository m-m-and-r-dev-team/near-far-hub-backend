<?php

declare(strict_types=1);

namespace App\Http\Requests\Images;

use Illuminate\Foundation\Http\FormRequest;

class DeleteImageRequest extends FormRequest
{
    const IMAGE_ID = 'imageId';

    public function rules(): array
    {
        return [
            self::IMAGE_ID => 'required',
        ];
    }

    public function validationData(): array
    {
        return array_merge($this->all(), [
            self::IMAGE_ID => $this->route(self::IMAGE_ID),
        ]);
    }

    public function getImageId(): int
    {
        return (int) $this->route(self::IMAGE_ID);
    }
}
