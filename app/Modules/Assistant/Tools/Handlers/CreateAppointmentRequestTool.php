<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Models\Appointment;
use App\Modules\Appointment\Actions\CreateAppointmentRequest;
use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Assistant\Tools\Support\AppointmentInput;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;

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
                'description' => 'Create an appointment request after the guest confirms the service, stylist, date and start time and you have their name and phone. This re-checks availability before booking. It does not charge payment — it creates a pending appointment for the salon team to confirm.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_slug' => ['type' => 'string'],
                        'stylist_slug' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'time_slot' => ['type' => 'string', 'description' => 'Start time, HH:MM, on the half hour'],
                        'occasion' => ['type' => 'string', 'enum' => Appointment::OCCASIONS],
                        'guest_name' => ['type' => 'string'],
                        'guest_email' => ['type' => 'string'],
                        'guest_phone' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => ['service_slug', 'stylist_slug', 'date', 'time_slot', 'guest_name', 'guest_phone'],
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

        if ($this->availability->isSlotInPast($salon, $date, $slot)) {
            return new ToolResult('That start time has already passed. Ask the guest for a later time.');
        }

        $occasion = in_array($arguments['occasion'] ?? null, Appointment::OCCASIONS, true) ? $arguments['occasion'] : null;

        $appointment = $this->create->handle($salon, $service, $stylist, [
            'appointment_date' => $date,
            'time_slot' => $slot,
            'occasion' => $occasion,
            'guest_name' => $arguments['guest_name'],
            'guest_email' => $arguments['guest_email'] ?? null,
            'guest_phone' => $arguments['guest_phone'],
            'notes' => $arguments['notes'] ?? null,
        ], $context->conversation);

        if (! $appointment) {
            return new ToolResult('The stylist is no longer free for the full length of that service — availability may have just changed. Call check_availability again or offer another start time.');
        }

        $payload = [
            'appointment_reference' => $appointment->reference,
            'service_slug' => $service->slug,
            'service_name' => $service->translatedName($context->locale),
            'stylist_slug' => $stylist->slug,
            'name' => $stylist->translatedName($context->locale),
            'date' => $date->toDateString(),
            'time_slot' => $slot,
            'time_range' => $appointment->time_range,
            'duration_minutes' => $appointment->duration_minutes,
            'deposit_total' => (float) $appointment->deposit_total,
            'currency' => $salon->currency,
            'status' => 'pending_confirmation',
        ];

        return ToolResult::json($payload, ['type' => 'appointment_confirmation', 'appointment' => $payload]);
    }
}
