<?php

namespace App\Modules\Appointment\Actions;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Support\StylistAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creates a pending appointment request and holds the stylist's time for it.
 * Both the booking wizard and the AI Beautician's appointment tool end up here.
 */
class CreateAppointmentRequest
{
    public function __construct(private readonly StylistAvailability $availability) {}

    /**
     * Returns null when the stylist is no longer free for the whole service.
     *
     * @param  array{appointment_date: CarbonImmutable, time_slot: string, occasion?: ?string, guest_name: string, guest_email?: ?string, guest_phone?: ?string, contact_type?: ?string, notes?: ?string}  $data
     */
    public function handle(Salon $salon, Service $service, Stylist $stylist, array $data, ?Conversation $conversation = null): ?Appointment
    {
        return DB::transaction(function () use ($salon, $service, $stylist, $data, $conversation) {
            $quote = $this->availability->quote($stylist, $service, $data['appointment_date'], $data['time_slot'], lockForUpdate: true);

            if (! $quote) {
                return null;
            }

            $appointment = Appointment::create([
                'reference' => Appointment::generateReference(),
                'salon_id' => $salon->id,
                'service_id' => $service->id,
                'stylist_id' => $stylist->id,
                'conversation_id' => $conversation?->id,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'contact_type' => $data['contact_type'] ?? null,
                'appointment_date' => $data['appointment_date']->toDateString(),
                'time_slot' => $data['time_slot'],
                'duration_minutes' => $quote['duration_minutes'],
                'occasion' => $data['occasion'] ?? null,
                'deposit_total' => $quote['deposit'],
                'status' => Appointment::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->availability->setBooked($stylist, $data['appointment_date'], StylistAvailability::blocks($data['time_slot'], $service->blockCount()), true);

            return $appointment;
        });
    }
}
