<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\DataTransferObjects\Auth\RegisterRequestData;
use App\Http\Resources\Auth\AuthResource;
use App\Http\DataTransferObjects\Auth\AuthResponseData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class RegisterRequest extends FormRequest
{
    private const NAME = 'name';
    private const EMAIL = 'email';
    private const PASSWORD = 'password';
    private const PASSWORD_CONFIRMATION = 'password_confirmation';

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
                ValidationRuleHelper::max(255)
            ],
            self::EMAIL => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::EMAIL,
                ValidationRuleHelper::max(255),
                ValidationRuleHelper::unique(User::class, 'email')
            ],
            self::PASSWORD => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(8),
                ValidationRuleHelper::CONFIRMED
            ],
            self::PASSWORD_CONFIRMATION => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): RegisterRequestData
    {
        return new RegisterRequestData([
            RegisterRequestData::NAME => $this->input(self::NAME),
            RegisterRequestData::EMAIL => $this->input(self::EMAIL),
            RegisterRequestData::PASSWORD => $this->input(self::PASSWORD),
            RegisterRequestData::PASSWORD_CONFIRMATION => $this->input(self::PASSWORD_CONFIRMATION),
        ]);
    }

    public function responseResource(AuthResponseData $authResponse): AuthResource
    {
        return new AuthResource($authResponse);
    }
}