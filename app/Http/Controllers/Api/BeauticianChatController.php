<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveSalon;
use App\Http\Requests\Api\ConversationHistoryRequest;
use App\Http\Requests\Api\SendMessageRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Salon;
use App\Modules\Conversation\Actions\RememberUiContext;
use App\Modules\Conversation\Actions\ReplyToGuest;
use App\Modules\Conversation\Actions\StartConversation;
use App\Modules\Conversation\Support\MessagePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BeauticianChatController extends Controller
{
    public function start(Request $request, StartConversation $start): JsonResponse
    {
        $conversation = $start->handle(ResolveSalon::salon($request), ResolveSalon::locale($request));

        return response()->json([
            'guest_token' => $conversation->guest_token,
            'locale' => $conversation->locale,
        ]);
    }

    public function message(SendMessageRequest $request, RememberUiContext $remember, ReplyToGuest $reply): JsonResponse
    {
        $salon = ResolveSalon::salon($request);

        $conversation = $this->findConversation($salon, $request->validated('guest_token'));

        $remember->handle($salon, $conversation, $request->uiContext());

        try {
            $assistantMessage = $reply->handle($salon, $conversation, $request->validated('message'));
        } catch (\Throwable $e) {
            Log::error('Beautician reply failed', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'beautician_unavailable',
                'message' => 'The AI Beautician is temporarily unavailable. Please try again in a moment.',
            ], 503);
        }

        return response()->json([
            'message' => MessagePresenter::format($assistantMessage),
            'status' => $conversation->fresh()->status,
        ]);
    }

    public function history(ConversationHistoryRequest $request): JsonResponse
    {
        $conversation = $this->findConversation(ResolveSalon::salon($request), $request->validated('guest_token'));

        $messages = $conversation->messages()
            ->whereIn('role', [ConversationMessage::ROLE_GUEST, ConversationMessage::ROLE_ASSISTANT, ConversationMessage::ROLE_STAFF, ConversationMessage::ROLE_SYSTEM])
            ->orderBy('created_at')
            ->get()
            ->map(fn (ConversationMessage $m) => MessagePresenter::format($m))
            ->values();

        return response()->json([
            'messages' => $messages,
            'status' => $conversation->status,
        ]);
    }

    private function findConversation(Salon $salon, string $guestToken): Conversation
    {
        $conversation = Conversation::where('salon_id', $salon->id)
            ->where('guest_token', $guestToken)
            ->first();

        if (! $conversation) {
            throw ValidationException::withMessages([
                'guest_token' => 'This conversation no longer exists. Please start a new one.',
            ]);
        }

        return $conversation;
    }
}
