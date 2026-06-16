<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Website;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Conversation::with(['website.tenant', 'lead']);

        if (!$user->isSuperAdmin()) {
            $websiteIds = Website::where('tenant_id', $user->tenant_id)
                ->pluck('id');

            $query->whereIn('website_id', $websiteIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('lead_stage')) {
            $query->where('lead_stage', $request->lead_stage);
        }

        $conversations = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.conversations.index', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        $this->authorizeConversationAccess($conversation);

        $conversation->load([
            'website.tenant',
            'lead',
            'messages' => function ($query) {
                $query->orderBy('created_at');
            },
        ]);

        return view('admin.conversations.show', compact('conversation'));
    }

    private function authorizeConversationAccess(Conversation $conversation): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $website = Website::find($conversation->website_id);

        if (!$website || $website->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized conversation access.');
        }
    }
    public function reply(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        /*
        * Tenant users can only reply to conversations belonging
        * to their own tenant.
        */
        if (
            $user->role !== 'super_admin' &&
            (int) $conversation->tenant_id !== (int) $user->tenant_id
        ) {
            abort(403, 'You are not allowed to access this conversation.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'message'         => $validated['message'],
            'role'            => 'agent',
            'user_id'         => $user->id,
        ]);

        // Mark the conversation as being handled by a live agent.
        $conversation->update([
            'status'      => 'live_agent',
            'assigned_to' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $message->id,
                'message'    => $message->message,
                'role'       => $message->role,
                'created_at' => $message->created_at->toDateTimeString(),
            ],
        ]);
    }
}