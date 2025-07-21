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
    private const LOCATION = 'location';
    private const BIO = 'bio';

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
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(8),
                ValidationRuleHelper::max(20)
            ],
            self::LOCATION => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            self::BIO => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(500)
            ]
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
            UpdateProfileRequestData::LOCATION => $this->input(self::LOCATION),
            UpdateProfileRequestData::BIO => $this->input(self::BIO),
        ]);
    }

    public function responseResource(User $user): UserResource
    {
        return new UserResource($user);
    }
}