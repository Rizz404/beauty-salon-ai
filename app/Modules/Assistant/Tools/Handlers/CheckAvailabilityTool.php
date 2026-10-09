<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Assistant\Tools\Support\AppointmentInput;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;

final class CheckAvailabilityTool implements Tool
{
    public function __construct(
        private readonly StylistAvailability $availability,
        private readonly AppointmentInput $input,
    ) {}

    public function name(): string
    {
        return 'check_availability';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Get the current, real-time availability, exact price, duration and deposit for one service with one stylist on a specific date and start time. ALWAYS call this before telling a guest a time is free or confirming a price — never state availability from memory.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_slug' => ['type' => 'string'],
                        'stylist_slug' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'time_slot' => ['type' => 'string', 'description' => 'Start time, HH:MM, on the half hour'],
                    ],
                    'required' => ['service_slug', 'stylist_slug', 'date', 'time_slot'],
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

        $quote = $this->availability->isSlotInPast($salon, $date, $slot)
            ? null
            : $this->availability->quote($stylist, $service, $date, $slot);

        if (! $quote) {
            $alternatives = array_map(
                fn (string $s) => ['time_slot' => $s, 'time_range' => StylistAvailability::range($s, $service->duration_minutes)],
                $this->availability->availableStarts($stylist, $service, $date),
            );

            return ToolResult::json([
                'available' => false,
                'service_slug' => $service->slug,
                'stylist_slug' => $stylist->slug,
                'message' => 'This stylist is not free for the full length of the service at that time.',
                'other_available_start_times_that_day' => $alternatives,
            ], ['type' => 'availability', 'available' => false, 'service_slug' => $service->slug, 'stylist_slug' => $stylist->slug]);
        }

        $result = [
            'available' => true,
            'service_slug' => $service->slug,
            'service_name' => $service->translatedName($context->locale),
            'stylist_slug' => $stylist->slug,
            'name' => $stylist->translatedName($context->locale),
            'date' => $quote['date'],
            'time_slot' => $quote['time_slot'],
            'time_range' => $quote['time_range'],
            'duration_minutes' => $quote['duration_minutes'],
            'price' => $quote['price'],
            'deposit' => $quote['deposit'],
            'currency' => $salon->currency,
        ];

        return ToolResult::json($result, ['type' => 'availability', 'available' => true, 'quote' => $result]);
    }
}
