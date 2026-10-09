<?php

namespace App\Modules\Conversation\Actions;

use App\Models\Conversation;
use App\Models\Salon;
use Illuminate\Support\Str;

class StartConversation
{
    public function handle(Salon $salon, string $locale = 'id'): Conversation
    {
        return Conversation::create([
            'salon_id' => $salon->id,
            'guest_token' => (string) Str::uuid(),
            'locale' => $locale,
            'status' => Conversation::STATUS_ACTIVE,
            'last_message_at' => now(),
        ]);
    }
}
