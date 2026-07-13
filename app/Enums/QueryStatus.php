<?php

namespace App\Enums;

enum QueryStatus: int
{
    case ACTUAL = 11;
    case LOOKING_FOR_CARRIER = 12;
    case CARRIER_RATE_OFFERED = 13;
    case WAITING_FOR_CUSTOMER = 14;
    case APPROVED = 15;
    case PLACED = 16;
    case CANCELED = 17;
    case COMMERCIAL_PROPOSAL_SENT = 18;

    public function label(): string
    {
        return match ($this) {
            self::ACTUAL => 'Actual',
            self::LOOKING_FOR_CARRIER => 'Looking for carrier',
            self::CARRIER_RATE_OFFERED => 'Carrier rate offered',
            self::WAITING_FOR_CUSTOMER => 'Waiting for customer',
            self::APPROVED => 'Approved',
            self::PLACED => 'Placed',
            self::CANCELED => 'Canceled',
            self::COMMERCIAL_PROPOSAL_SENT => 'Commercial proposal sent',
        };
    }
}