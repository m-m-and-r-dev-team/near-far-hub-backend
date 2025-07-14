<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\DataTransferObjects\Auth\LoginRequestData;
use App\Http\Resources\Auth\AuthResource;
use App\Http\DataTransferObjects\Auth\AuthResponseData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class LoginRequest extends FormRequest
{
    private const EMAIL = 'email';
    private const PASSWORD = 'password';
    private const REMEMBER = 'remember';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::EMAIL => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::EMAIL
            ],
            self::PASSWORD => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING
            ],
            self::REMEMBER => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): LoginRequestData
    {
        return new LoginRequestData([
            LoginRequestData::EMAIL => $this->input(self::EMAIL),
            LoginRequestData::PASSWORD => $this->input(self::PASSWORD),
            LoginRequestData::REMEMBER => $this->boolean(self::REMEMBER) ?? false,
        ]);
    }

    public function responseResource(AuthResponseData $authResponse): AuthResource
    {
        return new AuthResource($authResponse);
    }
}