<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Assistant\Tools\Support\AppointmentInput;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;

final class SearchStylistsTool implements Tool
{
    public function __construct(
        private readonly StylistAvailability $availability,
        private readonly AppointmentInput $input,
    ) {}

    public function name(): string
    {
        return 'search_stylists';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Find the stylists who perform a given service and are free on a date, with real-time availability already checked for the full duration of that service. Optionally narrow to one start time. Use this whenever a guest picks a service and a day and has not named a stylist, or asks who is free.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_slug' => ['type' => 'string', 'description' => 'The service, from a search_services result'],
                        'date' => ['type' => 'string', 'description' => 'Appointment date, YYYY-MM-DD'],
                        'time_slot' => ['type' => 'string', 'description' => 'Optional start time, HH:MM, on the half hour'],
                    ],
                    'required' => ['service_slug', 'date'],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $salon = $context->salon;
        $service = CatalogSummaries::serviceBySlug($salon, (string) ($arguments['service_slug'] ?? ''));

        if (! $service) {
            return new ToolResult('Service not found. Call search_services first.');
        }

        $date = $this->input->parseDate($salon, (string) ($arguments['date'] ?? ''));

        if (! $date) {
            return new ToolResult('date must be a valid YYYY-MM-DD date.');
        }

        if ($error = $this->input->dateProblem($salon, $date)) {
            return new ToolResult($error);
        }

        $slot = $arguments['time_slot'] ?? null;

        if ($slot && ! $this->input->isKnownSlot($salon, (string) $slot)) {
            return new ToolResult($this->input->unknownSlotMessage($salon));
        }

        $matches = [];

        foreach ($this->availability->stylistsFor($service)->load('images') as $stylist) {
            $starts = $this->availability->availableStarts($stylist, $service, $date);

            if ($slot) {
                $starts = array_values(array_intersect($starts, [$slot]));
            }

            if ($starts === []) {
                continue;
            }

            $matches[] = [
                ...CatalogSummaries::stylist($stylist, $salon, $context->locale),
                'date' => $date->toDateString(),
                'available_slots' => array_map(fn (string $s) => ['time_slot' => $s, 'time_range' => StylistAvailability::range($s, $service->duration_minutes)], $starts),
            ];
        }

        if ($matches === []) {
            return new ToolResult('No stylist is free for the full length of that service on that date and time. Tell the guest honestly and offer a different time or date, or call request_human_handover for a group or bridal booking.');
        }

        return ToolResult::json($matches, [
            'type' => 'stylist_results',
            'date' => $date->toDateString(),
            'service' => CatalogSummaries::service($service, $salon, $context->locale),
            'stylists' => $matches,
        ]);
    }
}
