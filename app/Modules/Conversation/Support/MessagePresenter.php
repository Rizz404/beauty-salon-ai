<?php

namespace App\Modules\Conversation\Support;

use App\Models\ConversationMessage;

/**
 * A conversation message as the guest's browser receives it.
 */
final class MessagePresenter
{
    /**
     * @return array{role: string, content: ?string, ui_payload: ?array<int, array<string, mixed>>, suggested_actions: list<array{action: string, service?: string, stylist?: string}>, created_at: string}
     */
    public static function format(ConversationMessage $message): array
    {
        return [
            'role' => $message->role,
            'content' => $message->content,
            'ui_payload' => $message->ui_payload,
            'suggested_actions' => self::suggestedActions($message->ui_payload),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Interface actions that follow from what the beautician just showed, so the
     * guest can step into the matching scene instead of typing again.
     *
     * @param  list<array<string, mixed>>|null  $uiPayload
     * @return list<array{action: string, service?: string, stylist?: string}>
     */
    public static function suggestedActions(?array $uiPayload): array
    {
        $actions = [];

        foreach ($uiPayload ?? [] as $payload) {
            $type = $payload['type'] ?? null;
            $service = $payload['item']['service_slug'] ?? $payload['quote']['service_slug'] ?? null;
            $stylist = $payload['quote']['stylist_slug'] ?? null;

            if ($type === 'service_detail' && $service) {
                $actions[] = ['action' => 'view_service', 'service' => $service];
                $actions[] = ['action' => 'book', 'service' => $service];
            } elseif ($type === 'availability' && ($payload['available'] ?? false) && $stylist) {
                $actions[] = ['action' => 'book', 'service' => $service, 'stylist' => $stylist];
            } elseif ($type === 'handover') {
                $actions[] = ['action' => 'staff'];
            }
        }

        return collect($actions)->unique(fn (array $action) => $action['action'].($action['service'] ?? '').($action['stylist'] ?? ''))->values()->all();
    }
}
