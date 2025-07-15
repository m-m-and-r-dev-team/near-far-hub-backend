<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Http\DataTransferObjects\Seller\BookAppointmentData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class BookAppointmentRequest extends FormRequest
{
    private const SELLER_PROFILE_ID = 'seller_profile_id';
    private const LISTING_ID = 'listing_id';
    private const APPOINTMENT_DATETIME = 'appointment_datetime';
    private const DURATION_MINUTES = 'duration_minutes';
    private const BUYER_MESSAGE = 'buyer_message';
    private const MEETING_LOCATION = 'meeting_location';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::SELLER_PROFILE_ID => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::INTEGER,
                'exists:seller_profiles,id'
            ],
            self::LISTING_ID => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'exists:listings,id'
            ],
            self::APPOINTMENT_DATETIME => [
                ValidationRuleHelper::REQUIRED,
                'date',
                'after:now'
            ],
            self::DURATION_MINUTES => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::INTEGER,
                ValidationRuleHelper::min(15),
                ValidationRuleHelper::max(240)
            ],
            self::BUYER_MESSAGE => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(500)
            ],
            self::MEETING_LOCATION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): BookAppointmentData
    {
        return new BookAppointmentData([
            BookAppointmentData::SELLER_PROFILE_ID => $this->input(self::SELLER_PROFILE_ID),
            BookAppointmentData::LISTING_ID => $this->input(self::LISTING_ID),
            BookAppointmentData::APPOINTMENT_DATETIME => $this->input(self::APPOINTMENT_DATETIME),
            BookAppointmentData::DURATION_MINUTES => $this->input(self::DURATION_MINUTES),
            BookAppointmentData::BUYER_MESSAGE => $this->input(self::BUYER_MESSAGE),
            BookAppointmentData::MEETING_LOCATION => $this->input(self::MEETING_LOCATION),
        ]);
    }
}
