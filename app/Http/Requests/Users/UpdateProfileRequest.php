<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Http\DataTransferObjects\Users\UpdateProfileRequestData;
use App\Http\Resources\Users\UserResource;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UpdateProfileRequest extends FormRequest
{
    private const NAME = 'name';
    private const PHONE = 'phone';
    private const BIO = 'bio';
    private const LOCATION = 'location';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::NAME => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(2),
                ValidationRuleHelper::max(100)
            ],
            self::PHONE => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(8),
                ValidationRuleHelper::max(20)
            ],
            self::BIO => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(500)
            ],
            self::LOCATION => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::LOCATION . '.description' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            self::LOCATION . '.place_id' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING
            ],
            self::LOCATION . '.source' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                'in:local,external'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.min' => 'Name must be at least 2 characters',
            'phone.min' => 'Phone number must be at least 8 characters',
            'bio.max' => 'Bio cannot exceed 500 characters',
            'location.array' => 'Location must be a valid location object',
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): UpdateProfileRequestData
    {
        return new UpdateProfileRequestData([
            UpdateProfileRequestData::NAME => $this->input(self::NAME),
            UpdateProfileRequestData::PHONE => $this->input(self::PHONE),
            UpdateProfileRequestData::BIO => $this->input(self::BIO),
            UpdateProfileRequestData::LOCATION => $this->input(self::LOCATION),
        ]);
    }

    public function responseResource(User $user): UserResource
    {
        return new UserResource($user);
    }
}