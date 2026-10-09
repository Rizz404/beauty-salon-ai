<?php

namespace App\Modules\Appointment\Actions;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Appointment\Validation\AppointmentRules;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Turns the guest's already well-formed request into the service, stylist and
 * date it names, or tells them in their own language what does not fit.
 */
class ResolveAppointmentTarget
{
    public function __construct(private readonly StylistAvailability $availability) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  bool  $checkTime  false when only the service, stylist and day are being resolved
     * @return array{0: Service, 1: Stylist, 2: CarbonImmutable}
     *
     * @throws ValidationException
     */
    public function handle(Salon $salon, array $data, string $locale, bool $checkTime = true): array
    {
        $service = $salon->services()->where('is_active', true)->where('slug', $data['service_slug'])->first();

        if (! $service) {
            throw ValidationException::withMessages(['service_slug' => AppointmentRules::message('service_unknown', $locale)]);
        }

        $stylist = $salon->stylists()->where('is_active', true)->where('slug', $data['stylist_slug'])->first();

        if (! $stylist) {
            throw ValidationException::withMessages(['stylist_slug' => AppointmentRules::message('stylist_unknown', $locale)]);
        }

        if (! $this->availability->performs($stylist, $service)) {
            throw ValidationException::withMessages(['stylist_slug' => AppointmentRules::message('stylist_mismatch', $locale)]);
        }

        $date = CarbonImmutable::parse($data['appointment_date'], $salon->timezone)->startOfDay();
        $today = CarbonImmutable::now($salon->timezone)->startOfDay();

        if ($today->diffInDays($date) > StylistAvailability::MAX_ADVANCE_DAYS) {
            throw ValidationException::withMessages([
                'appointment_date' => AppointmentRules::message('date_far', $locale, ['max' => (string) StylistAvailability::MAX_ADVANCE_DAYS]),
            ]);
        }

        if (! $checkTime) {
            return [$service, $stylist, $date];
        }

        if (! in_array($data['time_slot'], $this->availability->startTimesFor($salon), true)) {
            throw ValidationException::withMessages(['time_slot' => AppointmentRules::message('slot_unknown', $locale)]);
        }

        if ($this->availability->isSlotInPast($salon, $date, $data['time_slot'])) {
            throw ValidationException::withMessages(['time_slot' => AppointmentRules::message('slot_past', $locale)]);
        }

        return [$service, $stylist, $date];
    }
}
