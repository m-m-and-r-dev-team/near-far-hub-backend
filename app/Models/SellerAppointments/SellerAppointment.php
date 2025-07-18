<?php

declare(strict_types=1);

namespace App\Models\SellerAppointments;

use App\Models\SellerProfiles\SellerProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerAppointment extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const SELLER_PROFILE_ID = 'seller_profile_id';
    public const BUYER_ID = 'buyer_id';
    public const LISTING_ID = 'listing_id';
    public const APPOINTMENT_DATETIME = 'appointment_datetime';
    public const DURATION_MINUTES = 'duration_minutes';
    public const STATUS = 'status';
    public const BUYER_MESSAGE = 'buyer_message';
    public const SELLER_RESPONSE = 'seller_response';
    public const MEETING_LOCATION = 'meeting_location';
    public const MEETING_NOTES = 'meeting_notes';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::SELLER_PROFILE_ID,
        self::BUYER_ID,
        self::LISTING_ID,
        self::APPOINTMENT_DATETIME,
        self::DURATION_MINUTES,
        self::STATUS,
        self::BUYER_MESSAGE,
        self::SELLER_RESPONSE,
        self::MEETING_LOCATION,
        self::MEETING_NOTES,
    ];

    protected $casts = [
        self::APPOINTMENT_DATETIME => 'datetime',
        self::DURATION_MINUTES => 'integer',
    ];

    /** @see SellerAppointment::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';
    /** @see SellerAppointment::buyerRelation() */
    const BUYER_RELATION = 'buyerRelation';

    public function sellerProfileRelation(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function buyerRelation(): BelongsTo
    {
        return $this->belongsTo(User::class, self::BUYER_ID);
    }

//    public function listing(): BelongsTo
//    {
//        return $this->belongsTo(Listing::class);
//    }

    public function getEndTimeAttribute(): \Carbon\Carbon
    {
        return $this->getAppointmentDatetime()->addMinutes($this->getDurationMinutes());
    }

    public function relatedSellerProfile(): SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    public function relatedBuyer(): User
    {
        return $this->{self::BUYER_RELATION};
    }

    public function getAppointmentDatetime(): Carbon
    {
        return $this->getAttribute(self::APPOINTMENT_DATETIME);
    }

    public function getDurationMinutes(): int
    {
        return $this->getAttribute(self::DURATION_MINUTES);
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getSellerProfileId(): int
    {
        return $this->getAttribute(self::SELLER_PROFILE_ID);
    }

    public function getBuyerId(): int
    {
        return $this->getAttribute(self::BUYER_ID);
    }
}