<?php

namespace App\Modules\Assistant\Tools\Support;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Support\StylistAvailability;
use Carbon\CarbonImmutable;

/**
 * Checks the date, start time and stylist the model passes to the appointment
 * tools, and words the problem so the model can ask the guest for something
 * that works.
 */
class AppointmentInput
{
    public function __construct(private readonly StylistAvailability $availability) {}

    public function parseDate(Salon $salon, string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value, $salon->timezone)?->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * A reason the date cannot be booked, or null when it is fine.
     */
    public function dateProblem(Salon $salon, CarbonImmutable $date): ?string
    {
        $today = CarbonImmutable::now($salon->timezone)->startOfDay();

        if ($date->lessThan($today)) {
            return 'That date is in the past. Ask the guest for a date from today onwards.';
        }

        if ($today->diffInDays($date) > StylistAvailability::MAX_ADVANCE_DAYS) {
            return 'Appointments can only be requested up to '.StylistAvailability::MAX_ADVANCE_DAYS.' days ahead. Offer an earlier date, or call request_human_handover for something further out.';
        }

        return null;
    }

    public function isKnownSlot(Salon $salon, string $slot): bool
    {
        return in_array($slot, $this->availability->startTimesFor($salon), true);
    }

    public function unknownSlotMessage(Salon $salon): string
    {
        return 'time_slot must be a start time on the half hour within opening hours: '.implode(', ', $this->availability->startTimesFor($salon)).'.';
    }

    /**
     * A reason the stylist cannot do the service, or null when they can.
     */
    public function stylistProblem(Stylist $stylist, Service $service): ?string
    {
        return $this->availability->performs($stylist, $service)
            ? null
            : "That stylist does not perform {$service->category} services. Call search_stylists to find who does.";
    }
}
