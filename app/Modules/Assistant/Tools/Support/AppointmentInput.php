<?php

namespace App\Modules\Assistant\Tools\Support;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Appointment\Validation\AppointmentRules;
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

    /**
     * A reason the guest's details cannot be used, or null when they are fine.
     * The model decides what to pass, so the same limits as the wizard apply.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function guestProblem(array $arguments): ?string
    {
        $name = trim((string) ($arguments['guest_name'] ?? ''));
        $phone = trim((string) ($arguments['guest_phone'] ?? ''));
        $email = trim((string) ($arguments['guest_email'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            return 'guest_name is missing or too long (100 characters at most). Ask the guest for their name.';
        }

        if ($phone === '' && $email === '') {
            return 'No way to reach the guest. Ask for a phone or WhatsApp number, or an email address, so the salon can confirm.';
        }

        if ($phone !== '' && ! preg_match(AppointmentRules::PHONE_PATTERN, $phone)) {
            return 'That phone number does not look valid. Ask the guest to check it.';
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'That email address does not look valid. Ask the guest to check it.';
        }

        if (mb_strlen((string) ($arguments['notes'] ?? '')) > 500) {
            return 'notes is too long (500 characters at most). Shorten it, or call request_human_handover for a detailed request.';
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
