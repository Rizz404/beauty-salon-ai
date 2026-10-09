<?php

namespace App\Modules\Conversation\Actions;

use App\Models\Conversation;
use App\Models\Salon;

/**
 * Remembers where in the UI the guest is, so the beautician can answer for "this
 * treatment" or the appointment they are filling in. Only values that exist for
 * this salon are kept; nothing personal is stored here.
 */
class RememberUiContext
{
    /**
     * @param  array{scene?: ?string, selected_service?: ?string, selected_stylist?: ?string, selected_facility?: ?int, appointment?: ?array<string, mixed>}  $context
     */
    public function handle(Salon $salon, Conversation $conversation, array $context): void
    {
        $updates = [];

        if (in_array($context['scene'] ?? null, Conversation::SCENES, true)) {
            $updates['current_scene'] = $context['scene'];
        }

        if (filled($context['selected_service'] ?? null)) {
            $service = $salon->services()->where('is_active', true)->where('slug', $context['selected_service'])->first();

            if ($service) {
                $updates['selected_service_id'] = $service->id;
            }
        }

        if (filled($context['selected_stylist'] ?? null)) {
            $stylist = $salon->stylists()->where('is_active', true)->where('slug', $context['selected_stylist'])->first();

            if ($stylist) {
                $updates['selected_stylist_id'] = $stylist->id;
            }
        }

        if (filled($context['selected_facility'] ?? null)) {
            $facility = $salon->knowledgeItems()->where('is_active', true)->whereKey($context['selected_facility'])->first();

            if ($facility) {
                $updates['selected_facility_id'] = $facility->id;
            }
        }

        if (array_key_exists('appointment', $context)) {
            $updates['appointment_state'] = $context['appointment'] ?: null;
        }

        if ($updates !== []) {
            $conversation->update($updates);
        }
    }
}
