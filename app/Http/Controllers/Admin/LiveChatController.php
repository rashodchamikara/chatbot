<?php

namespace App\Http\Controllers\Admin;

use App\Events\ConversationMessageCreated;
use App\Events\ConversationModeChanged;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Conversation::with(['website', 'assignedAgent'])
            ->whereIn('mode', ['live_waiting', 'live'])
            ->latest('live_requested_at');

        if (!$user->isSuperAdmin()) {
            $query->whereHas('website', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
            });
        }

        $conversations = $query->paginate(20);

        return view('admin.live-chat.index', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationAccess($request, $conversation);

        $conversation->load(['website', 'assignedAgent']);

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('id')
            ->get();

        return view('admin.live-chat.show', compact('conversation', 'messages'));
    }

    public function take(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationAccess($request, $conversation);

        $conversation->update([
            'mode' => 'live',
            'assigned_agent_id' => $request->user()->id,
            'live_started_at' => $conversation->live_started_at ?: now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'sender' => 'system',
            'is_system' => true,
            'message' => $request->user()->name . ' joined the chat.',
        ]);

        broadcast(new ConversationModeChanged($conversation->fresh()));
        broadcast(new ConversationMessageCreated($message));

        return redirect()
            ->route('admin.live-chat.show', $conversation)
            ->with('success', 'You joined the live chat.');
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationAccess($request, $conversation);

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($conversation->mode !== 'live') {
            $conversation->update([
                'mode' => 'live',
                'assigned_agent_id' => $request->user()->id,
                'live_started_at' => now(),
            ]);

            broadcast(new ConversationModeChanged($conversation->fresh()));
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'sender' => 'agent',
            'message' => $request->message,
        ]);

        broadcast(new ConversationMessageCreated($message));

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
        ]);
    }

    public function close(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationAccess($request, $conversation);

        $conversation->update([
            'mode' => 'ai',
            'assigned_agent_id' => null,
            'live_ended_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'sender' => 'system',
            'is_system' => true,
            'message' => 'Live chat ended. AI assistant is now active again.',
        ]);

        broadcast(new ConversationModeChanged($conversation->fresh()));
        broadcast(new ConversationMessageCreated($message));

        return redirect()
            ->route('admin.live-chat.index')
            ->with('success', 'Live chat closed.');
    }

    private function authorizeConversationAccess(Request $request, Conversation $conversation): void
    {
        $user = $request->user();

        $conversation->loadMissing('website');

        if ($user->isSuperAdmin()) {
            return;
        }

        if (!$conversation->website || $conversation->website->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized live chat access.');
        }
    }
}
