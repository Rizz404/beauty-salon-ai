<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;

final class GetServiceTool implements Tool
{
    public function name(): string
    {
        return 'get_service';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Get the full details of one service by its slug (from a previous search_services result): description, price, duration, deposit, cautions and whether it is temporarily unavailable. Use this when the guest asks about a specific treatment.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_slug' => ['type' => 'string'],
                    ],
                    'required' => ['service_slug'],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $service = CatalogSummaries::serviceBySlug($context->salon, (string) ($arguments['service_slug'] ?? ''));

        if (! $service) {
            return new ToolResult('Service not found.');
        }

        $detail = [
            ...CatalogSummaries::service($service, $context->salon, $context->locale),
            'description' => $service->translatedDescription($context->locale),
            'note' => 'price and duration are the current service-list values; cautions are guidance only and the stylist must confirm for guests with a known allergy or sensitive skin.',
        ];

        return ToolResult::json($detail, ['type' => 'service_detail', 'item' => $detail]);
    }
}
