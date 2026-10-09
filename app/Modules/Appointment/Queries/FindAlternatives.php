<?php

namespace App\Modules\Appointment\Queries;

use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Support\StylistAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The requested stylist cannot take the service at that time: find the other
 * stylists who can perform it at the same time, and the other start times of
 * the same stylist that day.
 */
class FindAlternatives
{
    public function __construct(private readonly StylistAvailability $availability) {}

    /**
     * @return Collection<int, array{type: string, slug?: string, time_slot?: string, name: string, deposit?: float}>
     */
    public function handle(Service $service, Stylist $requested, CarbonImmutable $date, string $timeSlot, string $locale): Collection
    {
        $otherStylists = $this->availability->stylistsFor($service)
            ->reject(fn (Stylist $stylist) => $stylist->is($requested))
            ->map(function (Stylist $stylist) use ($service, $date, $timeSlot, $locale) {
                $quote = $this->availability->quote($stylist, $service, $date, $timeSlot);

                return $quote ? [
                    'type' => 'stylist',
                    'slug' => $stylist->slug,
                    'name' => $stylist->translatedName($locale),
                    'deposit' => $quote['deposit'],
                ] : null;
            })
            ->filter()
            ->values();

        $otherSlots = collect($this->availability->availableStarts($requested, $service, $date))
            ->reject(fn (string $slot) => $slot === $timeSlot)
            ->map(fn (string $slot) => [
                'type' => 'slot',
                'time_slot' => $slot,
                'name' => StylistAvailability::range($slot, $service->duration_minutes),
            ])
            ->values();

        return $otherSlots->merge($otherStylists)->values();
    }
}
