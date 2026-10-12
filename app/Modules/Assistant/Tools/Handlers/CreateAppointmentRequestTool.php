<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Models\Appointment;
use App\Modules\Appointment\Actions\CreateAppointmentRequest;
use App\Modules\Appointment\Support\AppointmentHandover;
use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Assistant\Tools\Support\AppointmentInput;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;
use Carbon\CarbonImmutable;

/**
 * Only ever creates a pending request for the salon to confirm; it never
 * confirms an appointment and takes no payment.
 */
final class CreateAppointmentRequestTool implements Tool
{
    public function __construct(
        private readonly StylistAvailability $availability,
        private readonly AppointmentInput $input,
        private readonly CreateAppointmentRequest $create,
        private readonly AppointmentHandover $handover,
    ) {}

    public function name(): string
    {
        return 'create_appointment_request';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Create an appointment request once the guest has said yes to the full summary (service, stylist, date, start time, name, and a phone/WhatsApp number or email). Call it once per booking. This re-checks availability before booking. It does not charge payment — it creates a pending appointment for the salon team to confirm, and the guest sees the reference card on screen.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_slug' => ['type' => 'string'],
                        'stylist_slug' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'time_slot' => ['type' => 'string', 'description' => 'Start time, HH:MM, on the half hour'],
                        'occasion' => ['type' => 'string', 'enum' => Appointment::OCCASIONS],
                        'guest_name' => ['type' => 'string'],
                        'guest_phone' => ['type' => 'string', 'description' => 'Phone or WhatsApp number. Required unless guest_email is given.'],
                        'guest_email' => ['type' => 'string', 'description' => 'Required unless guest_phone is given.'],
                        'notes' => ['type' => 'string', 'description' => 'Special request, at most 500 characters'],
                    ],
                    'required' => ['service_slug', 'stylist_slug', 'date', 'time_slot', 'guest_name'],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $salon = $context->salon;
        $service = CatalogSummaries::serviceBySlug($salon, (string) ($arguments['service_slug'] ?? ''));

        if (! $service) {
            return new ToolResult('Service not found.');
        }

        $stylist = CatalogSummaries::stylistBySlug($salon, (string) ($arguments['stylist_slug'] ?? ''));

        if (! $stylist) {
            return new ToolResult('Stylist not found.');
        }

        if ($error = $this->input->stylistProblem($stylist, $service)) {
            return new ToolResult($error);
        }

        $date = $this->input->parseDate($salon, (string) ($arguments['date'] ?? ''));
        $slot = (string) ($arguments['time_slot'] ?? '');

        if (! $date) {
            return new ToolResult('date must be a valid YYYY-MM-DD date.');
        }

        if ($error = $this->input->dateProblem($salon, $date)) {
            return new ToolResult($error);
        }

        if (! $this->input->isKnownSlot($salon, $slot)) {
            return new ToolResult($this->input->unknownSlotMessage($salon));
        }

        if ($problem = $this->input->guestProblem($arguments)) {
            return new ToolResult($problem);
        }

        if ($this->availability->isSlotInPast($salon, $date, $slot)) {
            return new ToolResult('That start time has already passed. Ask the guest for a later time.');
        }

        $guestName = trim((string) $arguments['guest_name']);

        if ($existing = $this->alreadyRequested($context, $service->id, $stylist->id, $date, $slot, $guestName)) {
            return $this->confirmation($context, $existing, alreadyRequested: true);
        }

        $occasion = in_array($arguments['occasion'] ?? null, Appointment::OCCASIONS, true) ? $arguments['occasion'] : null;
        $phone = trim((string) ($arguments['guest_phone'] ?? '')) ?: null;
        $email = trim((string) ($arguments['guest_email'] ?? '')) ?: null;

        $appointment = $this->create->handle($salon, $service, $stylist, [
            'appointment_date' => $date,
            'time_slot' => $slot,
            'occasion' => $occasion,
            'guest_name' => $guestName,
            'guest_email' => $email,
            'guest_phone' => $phone,
            'contact_type' => $phone ? 'phone' : 'email',
            'notes' => trim((string) ($arguments['notes'] ?? '')) ?: null,
        ], $context->conversation);

        if (! $appointment) {
            return new ToolResult('The stylist is no longer free for the full length of that service — availability may have just changed. Call check_availability again or offer another start time.');
        }

        return $this->confirmation($context, $appointment);
    }

    /**
     * The model may repeat the call when the guest says yes twice; the same
     * request in the same conversation is answered, not booked again.
     */
    private function alreadyRequested(ToolContext $context, int $serviceId, int $stylistId, CarbonImmutable $date, string $slot, string $guestName): ?Appointment
    {
        return Appointment::query()
            ->where('conversation_id', $context->conversation->id)
            ->where('service_id', $serviceId)
            ->where('stylist_id', $stylistId)
            ->whereDate('appointment_date', $date->toDateString())
            ->where('time_slot', $slot)
            ->where('guest_name', $guestName)
            ->where('status', Appointment::STATUS_PENDING)
            ->first();
    }

    private function confirmation(ToolContext $context, Appointment $appointment, bool $alreadyRequested = false): ToolResult
    {
        $appointment->loadMissing(['service', 'stylist']);

        $payload = [
            'appointment_reference' => $appointment->reference,
            'service_slug' => $appointment->service->slug,
            'service_name' => $appointment->service->translatedName($context->locale),
            'stylist_slug' => $appointment->stylist->slug,
            'name' => $appointment->stylist->translatedName($context->locale),
            'date' => $appointment->appointment_date->toDateString(),
            'time_slot' => $appointment->time_slot,
            'time_range' => $appointment->time_range,
            'duration_minutes' => $appointment->duration_minutes,
            'deposit_total' => (float) $appointment->deposit_total,
            'currency' => $context->salon->currency,
            'status' => 'pending_confirmation',
        ];

        return ToolResult::json(
            $alreadyRequested ? [...$payload, 'note' => 'This request was already created earlier in this conversation; do not book again.'] : $payload,
            [
                'type' => 'appointment_confirmation',
                'appointment' => $payload,
                'handover' => $this->handover->forAppointment($context->salon, $appointment, $context->locale),
            ],
        );
    }
}
