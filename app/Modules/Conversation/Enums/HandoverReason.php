<?php

namespace App\Modules\Conversation\Enums;

/**
 * Why the beautician asked the team to take over.
 */
enum HandoverReason: string
{
    case SpecialRequest = 'special_request';
    case Complaint = 'complaint';
    case GroupAppointment = 'group_appointment';
    case BridalPackage = 'bridal_package';
    case SkinConcern = 'skin_concern';
    case PaymentIssue = 'payment_issue';
    case LowConfidence = 'low_confidence';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
