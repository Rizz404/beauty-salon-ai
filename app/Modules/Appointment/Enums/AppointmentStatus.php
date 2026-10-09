<?php

namespace App\Modules\Appointment\Enums;

/**
 * Where a appointment request stands: the salon confirms or cancels it.
 */
enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
