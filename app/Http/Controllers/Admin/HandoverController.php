<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentSalon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyToHandoverRequest;
use App\Models\HandoverRequest;
use App\Modules\Conversation\Actions\ReplyToHandover;
use App\Modules\Conversation\Actions\ResolveHandover;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HandoverController extends Controller
{
    use ResolvesCurrentSalon;

    public function index(Request $request): View
    {
        $salon = $this->currentSalon($request);

        $status = $request->query('status', HandoverRequest::STATUS_OPEN);

        $handovers = HandoverRequest::whereHas('conversation', fn ($q) => $q->where('salon_id', $salon->id))
            ->where('status', $status)
            ->with('conversation')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.handovers.index', compact('salon', 'handovers', 'status'));
    }

    public function show(Request $request, HandoverRequest $handover): View
    {
        $salon = $this->currentSalon($request);
        Gate::authorize('view', $handover);

        $handover->load('conversation.messages');

        return view('admin.handovers.show', compact('salon', 'handover'));
    }

    public function reply(ReplyToHandoverRequest $request, HandoverRequest $handover, ReplyToHandover $replyToHandover): RedirectResponse
    {
        $this->currentSalon($request);

        $replyToHandover->handle($handover, $request->validated('message'));

        return back()->with('status', 'Reply sent to the guest.');
    }

    public function resolve(Request $request, HandoverRequest $handover, ResolveHandover $resolveHandover): RedirectResponse
    {
        $this->currentSalon($request);
        Gate::authorize('update', $handover);

        $resolveHandover->handle($handover, $request->user());

        return redirect()->route('admin.handovers.index')->with('status', 'Handover resolved — the AI Beautician is back in the conversation.');
    }
}
