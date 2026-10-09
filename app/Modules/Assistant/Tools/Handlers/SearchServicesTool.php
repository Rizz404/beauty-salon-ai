<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Models\Service;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;
use App\Modules\Services\Queries\SearchServices;

final class SearchServicesTool implements Tool
{
    public function __construct(private readonly SearchServices $search) {}

    public function name(): string
    {
        return 'search_services';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Search the salon\'s service list by keyword, category, need, budget or time available. Returns matching treatments with their real price, duration, deposit, cautions and whether they are temporarily unavailable. Use this whenever a guest asks what the salon offers, wants a recommendation, or describes a goal, a need or a budget rather than naming one treatment.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Keywords such as "balayage", "gel", "hydrating", "wedding" — optional'],
                        'category' => [
                            'type' => 'string',
                            'enum' => Service::categories(),
                            'description' => 'Optional category filter',
                        ],
                        'need' => [
                            'type' => 'string',
                            'enum' => Service::NEED_TAGS,
                            'description' => 'Optional need, such as halal-friendly products or sensitive skin',
                        ],
                        'max_price' => ['type' => 'number', 'description' => 'Optional budget per treatment, in the salon currency'],
                        'max_minutes' => ['type' => 'integer', 'description' => 'Optional longest acceptable duration, in minutes'],
                    ],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $services = $this->search->handle(
            $context->salon,
            (string) ($arguments['query'] ?? ''),
            $arguments['category'] ?? null,
            $arguments['need'] ?? null,
            isset($arguments['max_price']) ? (float) $arguments['max_price'] : null,
            isset($arguments['max_minutes']) ? (int) $arguments['max_minutes'] : null,
        );

        if ($services->isEmpty()) {
            return new ToolResult('No service matches that request. Tell the guest honestly and suggest a nearby alternative from another search_services call, or call request_human_handover for custom requests.');
        }

        $results = $services->map(fn (Service $service) => CatalogSummaries::service($service, $context->salon, $context->locale))->values()->all();

        return ToolResult::json($results, ['type' => 'service_results', 'items' => $results]);
    }
}
