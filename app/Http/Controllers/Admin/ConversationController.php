<?php

namespace App\Http\Controllers\Admin;

use App\Events\ConversationMessageCreated;
use App\Events\ConversationModeChanged;
use App\Events\OmnichannelMessageChanged;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Omnichannel\OutboundMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index(
        Request $request
    ) {
        $user =
            $request->user();

        $query =
            Conversation::query()
                ->with([
                    'website.tenant',
                    'channelConnection.tenant',
                    'contact',
                    'lead',
                    'assignedAgent',
                ])
                ->withCount(
                    'messages'
                );

        if (!$user->isSuperAdmin()) {
            $query->where(
                function ($query) use ($user): void {
                    $query
                        ->where(
                            'tenant_id',
                            $user->tenant_id
                        )
                        ->orWhere(
                            function ($legacy) use ($user): void {
                                $legacy
                                    ->whereNull(
                                        'tenant_id'
                                    )
                                    ->whereHas(
                                        'website',
                                        function ($websiteQuery) use ($user): void {
                                            $websiteQuery
                                                ->where(
                                                    'tenant_id',
                                                    $user->tenant_id
                                                );
                                        }
                                    );
                            }
                        );
                }
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string(
                    'status'
                )
            );
        }

        if ($request->filled('lead_stage')) {
            $query->where(
                'lead_stage',
                $request->string(
                    'lead_stage'
                )
            );
        }

        if ($request->filled('mode')) {
            $query->where(
                'mode',
                $request->string(
                    'mode'
                )
            );
        }

        if ($request->filled('channel_type')) {
            $query->whereHas(
                'channelConnection',
                function ($channelQuery) use ($request): void {
                    $channelQuery->where(
                        'type',
                        $request->string('channel_type')->toString()
                    );
                }
            );
        }

        $conversations =
            $query
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
            'channelConnection.tenant',
            'contact',
            'lead',
            'assignedAgent',

            'messages' =>
                function ($query): void {
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

        $user =
            $request->user();

        $result =
            DB::transaction(
                function () use (
                    $conversation,
                    $user
                ): array {
                    $lockedConversation =
                        Conversation::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $conversation->id
                            );

                    if (
                        $lockedConversation->assigned_agent_id
                        &&
                        (int)
                        $lockedConversation->assigned_agent_id
                        !==
                        (int)
                        $user->id
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

                        'assigned_agent_id' =>
                            $user->id,

                        'assigned_user_id' =>
                            $user->id,

                        'live_started_at' =>
                            $lockedConversation
                                ->live_started_at
                            ?: now(),

                        'live_ended_at' =>
                            null,
                    ]);

                    $systemMessage =
                        Message::create([
                            'conversation_id' =>
                                $lockedConversation->id,

                            'channel_connection_id' =>
                                $lockedConversation
                                    ->channel_connection_id,

                            'user_id' =>
                                $user->id,

                            'sender_user_id' =>
                                $user->id,

                            'sender' =>
                                'system',

                            'sender_type' =>
                                'system',

                            'direction' =>
                                'outbound',

                            'message_type' =>
                                'text',

                            'status' =>
                                'sent',

                            'is_ai_generated' =>
                                false,

                            'is_system' =>
                                true,

                            'sent_at' =>
                                now(),

                            'message' =>
                                $user->name
                                . ' joined the live chat.',
                        ]);

                    return [
                        'error' => false,

                        'conversation' =>
                            $lockedConversation
                                ->fresh(),

                        'message' =>
                            $systemMessage,
                    ];
                }
            );

        if ($result['error']) {
            return response()->json([
                'message' =>
                    $result['message'],
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

        OmnichannelMessageChanged::dispatch(
            $result['message'],
            'created'
        );

        return response()->json([
            'success' => true,

            'mode' => 'live',

            'assigned_agent' => [
                'id' =>
                    $user->id,

                'name' =>
                    $user->name,
            ],
        ]);
    }

    public function sendMessage(
        Request $request,
        Conversation $conversation,
        OutboundMessageService $outboundMessageService
    ): JsonResponse {
        $this->authorizeConversationAccess(
            $request,
            $conversation
        );

        $validated =
            $request->validate([
                'message' => [
                    'required',
                    'string',
                    'max:5000',
                ],
            ]);

        $user =
            $request->user();

        $conversation->refresh();

        if ($conversation->mode !== 'live') {
            return response()->json([
                'message' =>
                    'Take the conversation before sending a reply.',
            ], 409);
        }

        if (
            (int)
            $conversation->assigned_agent_id
            !==
            (int)
            $user->id
        ) {
            return response()->json([
                'message' =>
                    'This conversation is assigned to another agent.',
            ], 403);
        }

        $message =
            $outboundMessageService
                ->send(
                    conversation:
                        $conversation,

                    body:
                        trim(
                            $validated['message']
                        ),

                    senderType:
                        'agent',

                    senderUserId:
                        $user->id,

                    isAiGenerated:
                        false,

                    metadata: [
                        'source' =>
                            'live_agent',
                    ],
                );

        return response()->json([
            'success' => true,

            'message' =>
                $this->formatMessage(
                    $message
                ),
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

        $user =
            $request->user();

        $conversation->refresh();

        if (
            $conversation->assigned_agent_id
            &&
            (int)
            $conversation->assigned_agent_id
            !==
            (int)
            $user->id
            &&
            !$user->isSuperAdmin()
        ) {
            return response()->json([
                'message' =>
                    'This conversation is assigned to another agent.',
            ], 403);
        }

        $result =
            DB::transaction(
                function () use (
                    $conversation,
                    $user
                ): array {
                    $conversation->update([
                        'mode' => 'ai',

                        'assigned_agent_id' =>
                            null,

                        'assigned_user_id' =>
                            null,

                        'live_ended_at' =>
                            now(),
                    ]);

                    $message =
                        Message::create([
                            'conversation_id' =>
                                $conversation->id,

                            'channel_connection_id' =>
                                $conversation
                                    ->channel_connection_id,

                            'user_id' =>
                                $user->id,

                            'sender_user_id' =>
                                $user->id,

                            'sender' =>
                                'system',

                            'sender_type' =>
                                'system',

                            'direction' =>
                                'outbound',

                            'message_type' =>
                                'text',

                            'status' =>
                                'sent',

                            'is_ai_generated' =>
                                false,

                            'is_system' =>
                                true,

                            'sent_at' =>
                                now(),

                            'message' =>
                                'Live chat ended. The AI assistant is active again.',
                        ]);

                    return [
                        'conversation' =>
                            $conversation
                                ->fresh(),

                        'message' =>
                            $message,
                    ];
                }
            );

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

        OmnichannelMessageChanged::dispatch(
            $result['message'],
            'created'
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
        $user =
            $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if (
            $conversation->tenant_id
            !== null
        ) {
            abort_unless(
                (int)
                $conversation->tenant_id
                ===
                (int)
                $user->tenant_id,
                403,
                'Unauthorized conversation access.'
            );

            return;
        }

        $conversation->loadMissing(
            'website'
        );

        abort_unless(
            $conversation->website
            &&
            (int)
            $conversation->website->tenant_id
            ===
            (int)
            $user->tenant_id,
            403,
            'Unauthorized conversation access.'
        );
    }

    private function formatMessage(
        Message $message
    ): array {
        $message->loadMissing(
            'user'
        );

        return [
            'id' =>
                $message->id,

            'conversation_id' =>
                $message->conversation_id,

            'sender' =>
                $message->sender,

            'message' =>
                $message->message,

            'is_system' =>
                (bool)
                $message->is_system,

            'agent_name' =>
                $message->user?->name,

            'created_at' =>
                $message
                    ->created_at
                    ?->toISOString(),
        ];
    }
}