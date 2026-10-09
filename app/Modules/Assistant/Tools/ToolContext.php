<?php

namespace App\Modules\Assistant\Tools;

use App\Models\Conversation;
use App\Models\Salon;

/**
 * What a tool may act on: one salon, one conversation, in the guest's language.
 */
final readonly class ToolContext
{
    public function __construct(
        public Salon $salon,
        public Conversation $conversation,
        public string $locale,
    ) {}
}
