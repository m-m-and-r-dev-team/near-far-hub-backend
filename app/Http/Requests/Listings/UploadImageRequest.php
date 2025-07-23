<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => [
                ValidationRuleHelper::REQUIRED,
                'array',
                'max:10'
            ],
            'images.*' => [
                ValidationRuleHelper::REQUIRED,
                'file',
                'image',
                'max:10240', // 10MB
                'mimes:jpeg,jpg,png,gif,webp'
            ],
            'type' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                Rule::in(['listing', 'listing_gallery', 'listing_thumbnail'])
            ],
            'alt_text' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            'quality' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                'min:60',
                'max:100'
            ],
            'max_width' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                'min:100',
                'max:4000'
            ],
            'max_height' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                'min:100',
                'max:4000'
            ]
        ];
    }
}