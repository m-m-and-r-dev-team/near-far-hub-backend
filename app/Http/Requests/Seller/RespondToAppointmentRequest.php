<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Http\DataTransferObjects\Seller\RespondToAppointmentData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class RespondToAppointmentRequest extends FormRequest
{
    private const STATUS = 'status';
    private const SELLER_RESPONSE = 'seller_response';
    private const MEETING_LOCATION = 'meeting_location';
    private const MEETING_NOTES = 'meeting_notes';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::STATUS => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                'in:approved,rejected'
            ],
            self::SELLER_RESPONSE => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(500)
            ],
            self::MEETING_LOCATION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255)
            ],
            self::MEETING_NOTES => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(1000)
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): RespondToAppointmentData
    {
        return new RespondToAppointmentData([
            RespondToAppointmentData::STATUS => $this->input(self::STATUS),
            RespondToAppointmentData::SELLER_RESPONSE => $this->input(self::SELLER_RESPONSE),
            RespondToAppointmentData::MEETING_LOCATION => $this->input(self::MEETING_LOCATION),
            RespondToAppointmentData::MEETING_NOTES => $this->input(self::MEETING_NOTES),
        ]);
    }
}