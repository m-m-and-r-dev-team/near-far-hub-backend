<?php

declare(strict_types=1);

namespace App\Http\Requests\Images;

use Illuminate\Foundation\Http\FormRequest;

class GetImagesRequest extends FormRequest
{
    const RELATED_ID = 'relatedId';
    const TYPE = 'type';

    public function rules(): array
    {
        return [
            self::RELATED_ID => 'required',
            self::TYPE => 'required',
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

    public function getType(): string
    {
        return (string) $this->input(self::TYPE);
    }
}
