<?php

namespace App\Modules\Operations\Presenters;

/**
 * The admin menu: where each entry leads and what it is called.
 */
final class AdminNavigation
{
    /**
     * @return list<array{route: string, label: string}>
     */
    public static function items(): array
    {
        return [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
            ['route' => 'admin.services.index', 'label' => 'Services'],
            ['route' => 'admin.stylists.index', 'label' => 'Stylists'],
            ['route' => 'admin.knowledge-items.index', 'label' => 'Knowledge base'],
            ['route' => 'admin.appointments.index', 'label' => 'Appointments'],
            ['route' => 'admin.handovers.index', 'label' => 'Handovers'],
        ];
    }
}
