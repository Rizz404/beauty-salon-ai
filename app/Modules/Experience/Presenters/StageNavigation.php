<?php

namespace App\Modules\Experience\Presenters;

use App\Models\Salon;

/**
 * The main menu of the stage. Sections that live on another page are marked
 * so the stage can fade out, as if the beautician were walking the guest over.
 */
final class StageNavigation
{
    /**
     * @param  array<string, string>  $labels
     * @return list<array{key: string, icon: string, label: string, chip: string, href: string, exit: bool, tour: ?string, topic: string}>
     */
    public static function for(Salon $salon, string $locale, array $labels): array
    {
        $tour = StageCopy::narration($locale);
        $url = fn (string $route) => route($route, ['salonSlug' => $salon->slug, 'lang' => $locale]);

        return [
            ['key' => 'services', 'icon' => 'scissors', 'label' => $labels['services_heading'], 'chip' => $labels['chip_services'], 'href' => $url('salon.services'), 'exit' => true, 'tour' => $tour['tour_services'], 'topic' => $labels['nav_services_q']],
            ['key' => 'stylists', 'icon' => 'user', 'label' => $labels['stylists_heading'], 'chip' => $labels['chip_stylists'], 'href' => $url('salon.stylists'), 'exit' => true, 'tour' => $tour['tour_stylists'], 'topic' => $labels['nav_stylists_q']],
            ['key' => 'facilities', 'icon' => 'sparkle', 'label' => $labels['facilities_heading'], 'chip' => $labels['chip_facilities'], 'href' => $url('salon.facilities'), 'exit' => true, 'tour' => $tour['tour_facilities'], 'topic' => $labels['nav_facilities_q']],
            ['key' => 'info', 'icon' => 'info', 'label' => $labels['info_heading'], 'chip' => $labels['chip_info'], 'href' => $url('salon.info'), 'exit' => true, 'tour' => $tour['tour_info'], 'topic' => $labels['nav_info_q']],
            ['key' => 'appointment', 'icon' => 'calendar', 'label' => $labels['appointment_heading'], 'chip' => $labels['chip_appointment'], 'href' => $url('salon.appointment'), 'exit' => true, 'tour' => $tour['tour_appointment'], 'topic' => $labels['nav_appointment_q']],
            ['key' => 'staff', 'icon' => 'chat', 'label' => $labels['staff_heading'], 'chip' => $labels['chip_staff'], 'href' => $url('salon.staff'), 'exit' => true, 'tour' => $tour['tour_staff'], 'topic' => $labels['nav_staff_q']],
        ];
    }
}
