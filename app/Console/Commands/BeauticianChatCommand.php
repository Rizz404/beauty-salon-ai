<?php

namespace App\Console\Commands;

use App\Models\Salon;
use App\Modules\Conversation\Actions\ReplyToGuest;
use App\Modules\Conversation\Actions\StartConversation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('beautician:chat {salon? : Salon slug} {--locale=id : id|en|ja}')]
#[Description('Talk to the AI Beautician for a salon from the terminal, for testing the tool-calling loop without the web UI.')]
class BeauticianChatCommand extends Command
{
    public function handle(StartConversation $start, ReplyToGuest $reply): int
    {
        $salon = $this->argument('salon')
            ? Salon::where('slug', $this->argument('salon'))->first()
            : Salon::first();

        if (! $salon) {
            $this->error('No salon found. Seed one first: php artisan db:seed');

            return self::FAILURE;
        }

        if (blank(config('services.local_llm.base_url'))) {
            $this->error('LOCAL_LLM_BASE_URL is not set in .env — the beautician has no model endpoint to call.');

            return self::FAILURE;
        }

        $locale = $this->option('locale');
        $conversation = $start->handle($salon, $locale);

        $this->info("Chatting with the AI Beautician for {$salon->name} ({$locale}). Type 'exit' to quit.");
        $this->newLine();

        while (true) {
            $guestMessage = $this->ask('You');

            if ($guestMessage === null || in_array(trim($guestMessage), ['exit', 'quit'], true)) {
                break;
            }

            $message = $reply->handle($salon, $conversation, $guestMessage);

            $this->newLine();
            $this->line('<fg=cyan>Beautician:</> '.($message->content ?? '(no text — see UI payload below)'));

            if ($message->ui_payload) {
                $this->line('<fg=gray>[ui_payload] '.json_encode($message->ui_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</>');
            }

            $conversation->refresh();
            if ($conversation->isHandedOver()) {
                $this->warn('Conversation handed over to the salon team. Ending session.');
                break;
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
