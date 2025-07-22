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
                'in:local,external,hybrid,local_enriched,external_enriched,geocoded,google_geocoding'
            ],
            // Add validation for other location fields that might be sent
            self::LOCATION . '.formatted_address' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(500)
            ],
            self::LOCATION . '.latitude' => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'between:-90,90'
            ],
            self::LOCATION . '.longitude' => [
                ValidationRuleHelper::NULLABLE,
                'numeric',
                'between:-180,180'
            ],
            self::LOCATION . '.data' => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            // Allow nested location data
            self::LOCATION . '.data.city_id' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'exists:cities,id'
            ],
            self::LOCATION . '.data.state_id' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'exists:states,id'
            ],
            self::LOCATION . '.data.country_id' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'exists:countries,id'
            ],
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
            'location.source.in' => 'Invalid location source. Must be one of: local, external, hybrid, local_enriched, external_enriched, geocoded, google_geocoding',
            'location.latitude.between' => 'Latitude must be between -90 and 90',
            'location.longitude.between' => 'Longitude must be between -180 and 180',
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