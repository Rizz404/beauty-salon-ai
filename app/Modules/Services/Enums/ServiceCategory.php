<?php

namespace App\Modules\Services\Enums;

/**
 * The kinds of treatment a salon's service list is grouped into. A stylist's
 * specialty is one of these, which is how a service finds the stylists who
 * can perform it.
 */
enum ServiceCategory: string
{
    case Hair = 'hair';
    case Nails = 'nails';
    case Skin = 'skin';
    case Makeup = 'makeup';
    case Spa = 'spa';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
