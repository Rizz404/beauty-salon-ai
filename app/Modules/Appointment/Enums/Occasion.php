<?php

namespace App\Modules\Appointment\Enums;

/**
 * Occasions a guest can mention so the team can prepare for them.
 */
enum Occasion: string
{
    case Wedding = 'wedding';
    case Party = 'party';
    case Photoshoot = 'photoshoot';
    case Graduation = 'graduation';
    case Everyday = 'everyday';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
