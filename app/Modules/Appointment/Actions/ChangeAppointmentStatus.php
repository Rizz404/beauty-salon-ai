<?php

namespace App\Modules\Appointment\Actions;

use App\Models\Appointment;
use App\Models\Service;
use App\Modules\Appointment\Support\StylistAvailability;

/**
 * Confirms or cancels an appointment request. Cancelling gives the stylist's
 * held time back to the schedule, once.
 */
class ChangeAppointmentStatus
{
    public function __construct(private readonly StylistAvailability $availability) {}

    public function handle(Appointment $appointment, string $status): Appointment
    {
        if ($status === Appointment::STATUS_CANCELLED && $appointment->status !== Appointment::STATUS_CANCELLED) {
            $this->releaseTime($appointment);
        }

        $appointment->update(['status' => $status]);

        return $appointment;
    }

    /**
     * Gives the blocks held by an appointment back to the stylist's schedule.
     */
    public function releaseTime(Appointment $appointment): void
    {
        $count = max(1, (int) ceil($appointment->duration_minutes / Service::BLOCK_MINUTES));

        $this->availability->setBooked(
            $appointment->stylist_id,
            $appointment->appointment_date->toDateString(),
            StylistAvailability::blocks($appointment->time_slot, $count),
            false,
        );
    }
}
