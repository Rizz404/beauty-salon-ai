<?php

namespace App\Modules\Appointment\Support;

use App\Models\Appointment;
use App\Models\Salon;

/**
 * Builds the WhatsApp / phone / email hand-over links that carry an
 * appointment summary (or a plain "talk to the team" request) to the salon.
 */
class AppointmentHandover
{
    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forAppointment(Salon $salon, Appointment $appointment, string $locale): array
    {
        $message = $this->appointmentMessage($appointment, $locale);

        return $this->links($salon, $message, $this->subject('appointment', $locale).' '.$appointment->reference);
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forStaff(Salon $salon, string $locale): array
    {
        return $this->links($salon, $this->subject('staff_message', $locale), $this->subject('staff', $locale));
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    private function links(Salon $salon, string $message, string $subject): array
    {
        $whatsapp = preg_replace('/\D+/', '', (string) $salon->whatsapp);
        $phone = preg_replace('/[^\d+]/', '', (string) $salon->phone);

        return [
            'whatsapp_url' => $whatsapp !== '' ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message) : null,
            'phone_url' => $phone !== '' ? 'tel:'.$phone : null,
            'email_url' => filled($salon->email) ? 'mailto:'.$salon->email.'?subject='.rawurlencode($subject).'&body='.rawurlencode($message) : null,
        ];
    }

    private function appointmentMessage(Appointment $appointment, string $locale): string
    {
        $appointment->loadMissing(['service', 'stylist']);

        $label = fn (string $key): string => trans('handover.message.'.$key, [], $locale);

        return implode("\n", [
            $label('intro'),
            '',
            $label('name').": {$appointment->guest_name}",
            $label('date').': '.$appointment->appointment_date->locale($locale)->translatedFormat('l, j F Y'),
            $label('time').': '.$appointment->time_range,
            $label('service').': '.$appointment->service->translatedName($locale),
            $label('stylist').': '.$appointment->stylist->translatedName($locale),
            $label('occasion').': '.($appointment->occasion ?: '-'),
            $label('special').': '.($appointment->notes ?: '-'),
            '',
            $label('reference').": {$appointment->reference}",
        ]);
    }

    private function subject(string $key, string $locale): string
    {
        return trans('handover.subject.'.$key, [], $locale);
    }
}
