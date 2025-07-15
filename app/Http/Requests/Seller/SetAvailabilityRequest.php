<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Http\DataTransferObjects\Seller\SetAvailabilityData;
use App\Http\DataTransferObjects\Seller\SellerAvailabilityData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class SetAvailabilityRequest extends FormRequest
{
    private const AVAILABILITY = 'availability';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::AVAILABILITY => [
                ValidationRuleHelper::REQUIRED,
                'array'
            ],
            self::AVAILABILITY . '.*.day_of_week' => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'
            ],
            self::AVAILABILITY . '.*.start_time' => [
                ValidationRuleHelper::REQUIRED,
                'date_format:H:i'
            ],
            self::AVAILABILITY . '.*.end_time' => [
                ValidationRuleHelper::REQUIRED,
                'date_format:H:i',
                'after:' . self::AVAILABILITY . '.*.start_time'
            ],
            self::AVAILABILITY . '.*.is_active' => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): SetAvailabilityData
    {
        $availability = [];
        foreach ($this->input(self::AVAILABILITY) as $slot) {
            $availability[] = new SellerAvailabilityData([
                SellerAvailabilityData::DAY_OF_WEEK => $slot['day_of_week'],
                SellerAvailabilityData::START_TIME => $slot['start_time'],
                SellerAvailabilityData::END_TIME => $slot['end_time'],
                SellerAvailabilityData::IS_ACTIVE => $slot['is_active'] ?? true,
            ]);
        }

        return new SetAvailabilityData([
            SetAvailabilityData::AVAILABILITY => $availability
        ]);
    }
}