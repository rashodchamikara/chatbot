<?php
namespace App\Http\Controllers\Admin;

use App\Events\ConversationMessageCreated;
use App\Events\ConversationModeChanged;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Conversation::query()
            ->with([
                'website.tenant',
                'lead',
                'assignedAgent',
            ])
            ->withCount('messages');

        if (!$user->isSuperAdmin()) {
            $query->whereHas('website', function ($websiteQuery) use ($user) {
                $websiteQuery->where('tenant_id', $user->tenant_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('lead_stage')) {
            $query->where('lead_stage', $request->string('lead_stage'));
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->string('mode'));
        }

        $conversations = $query
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.conversations.index',
            compact('conversations')
        );
    }

    public function show(
        Request $request,
        Conversation $conversation
    ) {
        $this->authorizeConversationAccess(
            $request,
            $conversation
        );

        $conversation->load([
            'website.tenant',
            'lead',
            'assignedAgent',
            'messages' => function ($query) {
                $query
                    ->with('user')
                    ->orderBy('id');
            },
        ]);

        return view(
            'admin.conversations.show',
            compact('conversation')
        );
    }

    public function take(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $this->authorizeConversationAccess(
            $request,
            $conversation
        );

        $user = $request->user();

        $result = DB::transaction(function () use (
            $conversation,
            $user
        ) {
            $lockedConversation = Conversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            if (
                $lockedConversation->assigned_agent_id &&
                (int) $lockedConversation->assigned_agent_id !==
                (int) $user->id
            ) {
                return [
                    'error' => true,
                    'status' => 409,
                    'message' =>
                        'Another agent has already taken this conversation.',
                ];
            }

            $lockedConversation->update([
                'mode' => 'live',
                'assigned_agent_id' => $user->id,
                'live_started_at' =>
                    $lockedConversation->live_started_at ?: now(),
                'live_ended_at' => null,
            ]);

            $systemMessage = Message::create([
                'conversation_id' => $lockedConversation->id,
                'user_id' => $user->id,
                'sender' => 'system',
                'is_system' => true,
                'message' => $user->name . ' joined the live chat.',
            ]);

            return [
                'error' => false,
                'conversation' => $lockedConversation->fresh(),
                'message' => $systemMessage,
            ];
        });

        if ($result['error']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

        broadcast(
            new ConversationModeChanged(
                $result['conversation']
            )
        );

        broadcast(
            new ConversationMessageCreated(
                $result['message']
            )
        );

        return response()->json([
            'success' => true,
            'mode' => 'live',
            'assigned_agent' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function sendMessage(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $this->authorizeConversationAccess(
            $request,
            $conversation
        );

        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $user = $request->user();

        $conversation->refresh();

        if ($conversation->mode !== 'live') {
            return response()->json([
                'message' =>
                    'Take the conversation before sending a reply.',
            ], 409);
        }

        if (
            (int) $conversation->assigned_agent_id !==
            (int) $user->id
        ) {
            return response()->json([
                'message' =>
                    'This conversation is assigned to another agent.',
            ], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'sender' => 'agent',
            'is_system' => false,
            'message' => trim($validated['message']),
        ]);

        $conversation->touch();

        $message->loadMissing('user', 'conversation');

        broadcast(
            new ConversationMessageCreated($message)
        );

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message),
        ]);
    }

    public function closeLiveChat(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $this->authorizeConversationAccess(
            $request,
            $conversation
        );

        $user = $request->user();

        $conversation->refresh();

        if (
            $conversation->assigned_agent_id &&
            (int) $conversation->assigned_agent_id !==
            (int) $user->id &&
            !$user->isSuperAdmin()
        ) {
            return response()->json([
                'message' =>
                    'This conversation is assigned to another agent.',
            ], 403);
        }

        $result = DB::transaction(function () use (
            $conversation,
            $user
        ) {
            $conversation->update([
                'mode' => 'ai',
                'assigned_agent_id' => null,
                'live_ended_at' => now(),
            ]);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'sender' => 'system',
                'is_system' => true,
                'message' =>
                    'Live chat ended. The AI assistant is active again.',
            ]);

            return [
                'conversation' => $conversation->fresh(),
                'message' => $message,
            ];
        });

        broadcast(
            new ConversationModeChanged(
                $result['conversation']
            )
        );

        broadcast(
            new ConversationMessageCreated(
                $result['message']
            )
        );

        return response()->json([
            'success' => true,
            'mode' => 'ai',
        ]);
    }

    private function authorizeConversationAccess(
        Request $request,
        Conversation $conversation
    ): void {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $conversation->loadMissing('website');

        abort_unless(
            $conversation->website &&
            (int) $conversation->website->tenant_id ===
            (int) $user->tenant_id,
            403,
            'Unauthorized conversation access.'
        );
    }

    private function formatMessage(Message $message): array
    {
        $message->loadMissing('user');

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender' => $message->sender,
            'message' => $message->message,
            'is_system' => (bool) $message->is_system,
            'agent_name' => $message->user?->name,
            'created_at' =>
                $message->created_at?->toISOString(),
        ];
    }
}