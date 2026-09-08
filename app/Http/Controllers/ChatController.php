<?php

namespace App\Http\Controllers;

use App\Events\ConversationMessageCreated;
use App\Events\ConversationModeChanged;
use App\Events\LiveAgentRequested;
use App\Events\OmnichannelMessageChanged;
use App\Models\Conversation;
use App\Models\Message;
<<<<<<< HEAD
use App\Models\Website;
=======
use App\Data\Omnichannel\InboundMessageData;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use App\Services\AgentAvailabilityService;
use App\Services\Knowledge\KnowledgeContextBuilder;
use App\Services\Knowledge\KnowledgeRetriever;
use App\Services\LeadCaptureService;
use App\Services\Omnichannel\ChannelManager;
use App\Services\Omnichannel\InboundMessageService;
use App\Services\Omnichannel\OutboundMessageService;
use App\Services\Omnichannel\WebsiteConversationResolver;
use App\Services\SalesBrainService;
<<<<<<< HEAD
use App\Support\Omnichannel\WebsiteIdentity;
=======
use App\Services\Omnichannel\ChannelManager;
use App\Services\Omnichannel\InboundMessageService;
use App\Services\Omnichannel\OutboundMessageService;
use App\Services\Omnichannel\WebsiteConversationResolver;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
<<<<<<< HEAD
use RuntimeException;
use Throwable;
=======
use App\Services\Knowledge\KnowledgeContextBuilder;
use App\Services\Knowledge\KnowledgeRetriever;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

class ChatController extends Controller
{
    public function __construct(
        private readonly KnowledgeRetriever $knowledgeRetriever,
        private readonly KnowledgeContextBuilder $contextBuilder,
<<<<<<< HEAD
        private readonly ChannelManager $channelManager,
        private readonly InboundMessageService $inboundMessageService,
        private readonly OutboundMessageService $outboundMessageService,
        private readonly WebsiteConversationResolver $websiteConversationResolver,
=======
        private readonly WebsiteConversationResolver $websiteConversationResolver,
        private readonly ChannelManager $channelManager,
        private readonly InboundMessageService $inboundMessageService,
        private readonly OutboundMessageService $outboundMessageService,
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    ) {
    }

    /**
     * Handle a message sent from the website widget.
     */
    public function message(
        Request $request,
        SalesBrainService $brain,
        LeadCaptureService $leadCaptureService
    ): JsonResponse {
<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Validate widget request
        |--------------------------------------------------------------------------
        */

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:5000',
            ],

            'visitor_id' => [
                'required',
                'string',
                'max:255',
            ],

            /*
             * Optional for backwards compatibility.
             *
             * Existing widget versions do not need to send this.
             * Future versions can send it for stronger idempotency.
             */
            'client_message_id' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Resolve Website from embed-token middleware
        |--------------------------------------------------------------------------
        */

        $website = $this->resolveWebsite(
            $request
        );
=======
        $website = $this->resolveWebsite($request);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved from the embed token.',
            ], 404);
        }

        /*
<<<<<<< HEAD
        |--------------------------------------------------------------------------
        | Resolve Website omnichannel conversation
        |--------------------------------------------------------------------------
        |
        | This ensures:
        |
        | Website
        |   -> AI Agent
        |   -> ChannelConnection
        |   -> Contact
        |   -> ContactIdentity
        |   -> Conversation
        |
        | Legacy visitor_id and website_id are preserved.
        |--------------------------------------------------------------------------
        */

        try {
            $conversation =
                $this
                    ->websiteConversationResolver
                    ->resolve(
                        $website,
                        $validated['visitor_id']
                    );

            $conversation->load(
                'channelConnection'
            );

            $connection =
                $conversation->channelConnection;

            if (!$connection) {
                throw new RuntimeException(
                    'Website channel connection could not be resolved.'
                );
            }
        } catch (RuntimeException $exception) {
            Log::error(
                'Website omnichannel conversation resolution failed.',
                [
                    'website_id' =>
                        $website->id,

                    'visitor_id' =>
                        $validated['visitor_id'],

                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    $exception->getMessage(),
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Build conversation history
        |--------------------------------------------------------------------------
        |
        | We build history BEFORE saving the current visitor message.
        |--------------------------------------------------------------------------
        */

        $history =
            Message::query()
                ->where(
                    'conversation_id',
                    $conversation->id
                )
                ->where(
                    'is_system',
                    false
                )
                ->whereIn(
                    'sender',
                    [
                        'visitor',
                        'ai',
                        'agent',
                    ]
                )
                ->latest('id')
                ->limit(10)
                ->get()
                ->reverse()
                ->map(
                    function (
                        Message $message
                    ): array {
                        return [
                            'role' =>
                                $message->sender === 'visitor'
                                    ? 'user'
                                    : 'assistant',

                            'content' =>
                                $message->message,
                        ];
                    }
                )
                ->values()
                ->toArray();
=======
         * Ensure the website has its native omnichannel connection,
         * contact identity and conversation while preserving the
         * legacy website_id + visitor_id lookup.
         */
        $conversation =
            $this->websiteConversationResolver
                ->resolve(
                    $website,
                    $validated['visitor_id']
                );

        if (!$conversation->mode) {
            $conversation->forceFill([
                'mode' => 'ai',
            ])->save();
        }

        /*
         * Load history before saving the current inbound message so
         * the current visitor message is not sent to the AI twice.
         */
        $history = Message::query()
            ->where(
                'conversation_id',
                $conversation->id
            )
            ->where('is_system', false)
            ->whereIn('sender', [
                'visitor',
                'ai',
                'agent',
            ])
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(function (Message $message): array {
                return [
                    'role' =>
                        $message->sender === 'visitor'
                            ? 'user'
                            : 'assistant',

                    'content' =>
                        $message->message,
                ];
            })
            ->values()
            ->toArray();
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        $connection =
            $this->websiteConversationResolver
                ->resolveConnection(
                    $website
                );

        $adapter =
            $this->channelManager
                ->forConnection(
                    $connection
                );

        $inboundData =
            $adapter->parseInbound(
                $connection,
                $request
            );

        if (!$inboundData) {
            return response()->json([
                'message' =>
                    'The website message could not be normalized.',
            ], 422);
        }

        /*
<<<<<<< HEAD
        |--------------------------------------------------------------------------
        | Resolve WebsiteAdapter
        |--------------------------------------------------------------------------
        */

        $adapter =
            $this
                ->channelManager
                ->forConnection(
                    $connection
                );

        /*
        |--------------------------------------------------------------------------
        | Normalize incoming widget request
        |--------------------------------------------------------------------------
        */

        $inbound =
            $adapter->parseInbound(
                $connection,
                $request
            );

        if (!$inbound) {
            return response()->json([
                'message' =>
                    'The website message could not be normalized.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Check whether provider/client message already exists
        |--------------------------------------------------------------------------
        |
        | InboundMessageService itself performs idempotency.
        |
        | We separately check here because the old website widget Reverb event
        | should only be broadcast for genuinely new visitor messages.
        |--------------------------------------------------------------------------
        */

        $alreadyExists =
            Message::query()
                ->where(
                    'channel_connection_id',
                    $connection->id
                )
                ->where(
                    'external_message_id',
                    $inbound->externalMessageId
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Persist inbound message through common omnichannel service
        |--------------------------------------------------------------------------
        */

        $visitorMessage =
            $this
                ->inboundMessageService
                ->handle(
                    $inbound
                );

        $visitorMessage->load(
            'conversation'
        );

        $conversation =
            $visitorMessage->conversation;

        /*
        |--------------------------------------------------------------------------
        | Preserve existing website Reverb event
        |--------------------------------------------------------------------------
        |
        | Existing widget/live-agent frontend already understands
        | ConversationMessageCreated.
        |--------------------------------------------------------------------------
        */

        if (!$alreadyExists) {
            broadcast(
                new ConversationMessageCreated(
                    $visitorMessage
                )
            );
        }

        $conversation->refresh();

        /*
        |--------------------------------------------------------------------------
        | Live-agent mode
        |--------------------------------------------------------------------------
        |
        | When the conversation is waiting for or currently handled by
        | a human agent, AI must not generate another reply.
        |--------------------------------------------------------------------------
        */

=======
         * Store the visitor message through the common omnichannel
         * inbound pipeline. This creates the new omnichannel fields
         * while preserving legacy sender values.
         */
        $visitorMessage =
            $this->inboundMessageService
                ->handle(
                    $connection,
                    $inboundData
                );

        $conversation =
            $visitorMessage->conversation
            ?? $conversation;

        $conversation->refresh();

        /*
         * Preserve the existing public website conversation event.
         */
        broadcast(
            new ConversationMessageCreated(
                $visitorMessage
            )
        );

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        if (
            in_array(
                $conversation->mode,
                [
                    'live_waiting',
                    'live',
                ],
                true
            )
        ) {
            return response()->json([
                'success' =>
                    true,

                'reply' =>
                    null,

                'reply_message' =>
                    null,

                'mode' =>
                    $conversation->mode,

                'conversation_id' =>
                    $conversation->id,

                'conversation_channel' =>
                    $this->conversationChannel(
                        $conversation
                    ),

                'message' =>
                    $conversation->mode === 'live'
                        ? 'Message sent to the live agent.'
                        : 'Message sent. Please wait for a live agent.',
            ]);
        }

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Lead capture
        |--------------------------------------------------------------------------
        */

        $leadResult =
            $leadCaptureService
                ->processMessage(
                    $website,
                    $conversation,
                    $validated['message']
                );

=======
        $leadResult =
            $leadCaptureService->processMessage(
                $website,
                $conversation,
                $validated['message']
            );

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        $lead =
            $leadResult['lead']
            ?? null;

        $leadStage =
            $leadResult['lead_stage']
            ?? $conversation->lead_stage
            ?? 'discovery';

        $nextLeadQuestion =
            $leadResult['next_question']
            ?? null;

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Knowledge retrieval
        |--------------------------------------------------------------------------
        */

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        $knowledgeResults = [];

        $knowledgeContext =
            'No relevant knowledge was found for this question.';

        try {
            $knowledgeResults =
                $this
                    ->knowledgeRetriever
                    ->retrieve(
                        $website,
                        $validated['message']
                    );

            $knowledgeContext =
<<<<<<< HEAD
                $this
                    ->contextBuilder
                    ->build(
                        $knowledgeResults
                    );
        } catch (Throwable $exception) {
            /*
             * Chat should continue even when knowledge retrieval fails.
             */
=======
                $knowledgeContextBuilder->build(
                    $knowledgeResults
                );
        } catch (\Throwable $exception) {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            Log::error(
                'Knowledge retrieval failed.',
                [
                    'website_id' =>
                        $website->id,

                    'conversation_id' =>
                        $conversation->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Generate AI response
        |--------------------------------------------------------------------------
        */

        try {
            $aiText =
                $brain->analyze(
                    $validated['message'],
                    $website,
                    $history,
                    $lead,
                    $leadStage,
                    $nextLeadQuestion,
                    $knowledgeContext
                );
        } catch (Throwable $exception) {
            Log::error(
                'AI response generation failed.',
                [
                    'website_id' =>
                        $website->id,

                    'conversation_id' =>
                        $conversation->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'The AI assistant could not generate a response right now.',

                'conversation_id' =>
                    $conversation->id,

                'conversation_channel' =>
                    $this->conversationChannel(
                        $conversation
                    ),
            ], 500);
        }

        $aiText =
            trim(
                (string)
                $aiText
            );
=======
        $aiText = $brain->analyze(
            $validated['message'],
            $website,
            $history,
            $lead,
            $leadStage,
            $nextLeadQuestion,
            $knowledgeContext
        );

        $aiText =
            trim((string) $aiText);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        if ($aiText === '') {
            $aiText =
                'Sorry, I could not generate a response right now.';
        }

        /*
<<<<<<< HEAD
        |--------------------------------------------------------------------------
        | Persist and deliver AI response
        |--------------------------------------------------------------------------
        |
        | AI replies now use the COMMON OutboundMessageService.
        |
        | WebsiteAdapter handles delivery through the existing Reverb
        | ConversationMessageCreated event.
        |--------------------------------------------------------------------------
        */

        try {
            $aiMessage =
                $this
                    ->outboundMessageService
                    ->send(
                        conversation:
                            $conversation,

                        body:
                            $aiText,

                        senderType:
                            'ai',

                        senderUserId:
                            null,

                        isAiGenerated:
                            true,

                        attachments:
                            [],

                        metadata: [
                            'source' =>
                                'website_ai',
                        ],
                    );
        } catch (Throwable $exception) {
            Log::error(
                'Website AI outbound delivery failed.',
                [
                    'website_id' =>
                        $website->id,

                    'conversation_id' =>
                        $conversation->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'The AI response could not be delivered.',

                'conversation_id' =>
                    $conversation->id,

                'conversation_channel' =>
                    $this->conversationChannel(
                        $conversation
                    ),
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | Return response expected by existing widget
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' =>
                true,

=======
         * Save/send the AI reply through the common outbound service.
         * WebsiteAdapter keeps the existing Reverb widget event.
         */
        $aiMessage =
            $this->outboundMessageService
                ->send(
                    conversation:
                        $conversation,

                    body:
                        $aiText,

                    senderType:
                        'ai',

                    senderUserId:
                        null,

                    isAiGenerated:
                        true,

                    attachments:
                        [],

                    metadata: [
                        'source' =>
                            'website_ai',
                    ],
                );

        $conversation->refresh();

        return response()->json([
            'success' => true,

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            'reply' =>
                $aiMessage->message,

            'reply_message' =>
                $this->formatMessage(
                    $aiMessage
                ),

            'mode' =>
                $conversation->mode
                ?: 'ai',

            'conversation_id' =>
                $conversation->id,

            'conversation_channel' =>
                $this->conversationChannel(
                    $conversation
                ),

            'lead' =>
                $lead
                    ? [
                        'id' =>
                            $lead->id,

                        'name' =>
                            $lead->name,

                        'email' =>
                            $lead->email,

                        'phone' =>
                            $lead->phone,

                        'country' =>
                            $lead->country,

                        'preferred_contact_time' =>
                            $lead->preferred_contact_time,

                        'product_interest' =>
                            $lead->product_interest,

                        'lead_score' =>
                            $lead->lead_score,

                        'status' =>
                            $lead->status,
                    ]
                    : null,

            'lead_stage' =>
                $leadStage,
        ]);
    }

<<<<<<< HEAD
    /**
     * Return public widget configuration.
     */
=======

  
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function config(
        Request $request,
        AgentAvailabilityService $agentAvailability
    ): JsonResponse {
        $website =
            $this->resolveWebsite(
                $request
            );

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved from the embed token.',
            ], 404);
        }

        $themes =
            config(
                'chatbot.themes',
                []
            );

        $defaultThemeKey =
            config(
                'chatbot.default_theme',
                'blue'
            );

        $fallbackTheme = [
            'label' =>
                'Blue',

            'primary' =>
                '#2563eb',

            'secondary' =>
                '#eff6ff',

            'text' =>
                '#ffffff',
        ];

        $themeKey =
            $website->chatbot_theme
            ?: $defaultThemeKey;

        $theme =
            $themes[$themeKey]
            ?? $themes[$defaultThemeKey]
            ?? $fallbackTheme;

        $realtimeKey =
            config(
                'chatbot.realtime.key'
            );

        $realtimeHost =
            config(
                'chatbot.realtime.host'
            );

        return response()->json([
            'website_id' =>
                $website->id,

            'chatbot_name' =>
                $website->chatbot_name
                ?: (
                    $website->name
                    . ' Assistant'
                ),

            'theme' => [
                'key' =>
                    $themeKey,

                'primary' =>
                    $theme['primary']
                    ?? $fallbackTheme['primary'],

                'secondary' =>
                    $theme['secondary']
                    ?? $fallbackTheme['secondary'],

                'text' =>
                    $theme['text']
                    ?? $fallbackTheme['text'],
            ],

            'avatar_url' =>
                $website->chatbot_avatar
                    ? asset(
                        'storage/'
                        . ltrim(
                            $website->chatbot_avatar,
                            '/'
                        )
                    )
                    : null,

            'live_agent_available' =>
                $agentAvailability
                    ->hasOnlineAgent(
                        $website
                    ),

            'realtime' => [
                'enabled' =>
                    filled($realtimeKey)
                    &&
                    filled($realtimeHost)
                    &&
                    filled(
                        $website->realtime_token
                    ),

                'key' =>
                    $realtimeKey,

                'host' =>
                    $realtimeHost,

                'port' =>
                    (int)
                    config(
                        'chatbot.realtime.port',
                        443
                    ),

                'scheme' =>
                    config(
                        'chatbot.realtime.scheme',
                        'https'
                    ),

                'website_channel' =>
                    filled(
                        $website->realtime_token
                    )
                        ? 'website.'
                            . $website->realtime_token
                        : null,
            ],
        ]);
    }

    /**
     * Return previous messages for a website visitor.
     */
    public function history(
        Request $request
    ): JsonResponse {
        $validated =
            $request->validate([
                'visitor_id' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'limit' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],
            ]);

        $website =
            $this->resolveWebsite(
                $request
            );

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved from the embed token.',
            ], 404);
        }

        $limit =
            (int)
            (
                $validated['limit']
                ?? 50
            );

        /*
         * History lookup should not create
         * a brand-new empty conversation.
         */
        $conversation =
            $this->findWebsiteConversation(
                $website,
                $validated['visitor_id']
            );

        if (!$conversation) {
            return response()->json([
                'conversation_id' =>
                    null,

                'conversation_channel' =>
                    null,

                'mode' =>
                    'ai',

                'messages' =>
                    [],
            ]);
        }

        $messages =
            $conversation
                ->messages()
                ->with('user')
                ->latest('id')
                ->limit($limit)
                ->get()
                ->sortBy('id')
                ->values()
                ->map(
                    function (
                        Message $message
                    ): array {
                        return
                            $this->formatMessage(
                                $message
                            );
                    }
                );

        return response()->json([
            'conversation_id' =>
                $conversation->id,

            'conversation_channel' =>
                $this->conversationChannel(
                    $conversation
                ),

            'mode' =>
                $conversation->mode
                ?: 'ai',

            'messages' =>
                $messages,
        ]);
    }

    /**
     * Visitor requests a human/live agent.
     */
    public function requestLiveAgent(
        Request $request,
        AgentAvailabilityService $agentAvailability
    ): JsonResponse {
        $validated =
            $request->validate([
                'visitor_id' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ]);

        $website =
            $this->resolveWebsite(
                $request
            );

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved.',
            ], 404);
        }

        if (
            !$agentAvailability
                ->hasOnlineAgent(
                    $website
                )
        ) {
            return response()->json([
                'message' =>
                    'No live agent is currently available.',

                'available' =>
                    false,
            ], 409);
        }

<<<<<<< HEAD
        /*
         * Live-agent request is a genuine visitor
         * interaction, so creating/upgrading the
         * omnichannel conversation is appropriate.
         */

        try {
            $conversation =
                $this
                    ->websiteConversationResolver
                    ->resolve(
                        $website,
                        $validated['visitor_id']
                    );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' =>
                    $exception->getMessage(),
            ], 409);
        }

        $result =
            DB::transaction(
                function () use (
                    $conversation
                ): array {
                    $conversation =
                        Conversation::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $conversation->id
                            );

                    if (
                        $conversation->mode
                        === 'live'
                    ) {
                        return [
                            'conversation' =>
                                $conversation,

                            'message' =>
                                null,

                            'already_requested' =>
                                true,

                            'response_message' =>
                                'A live agent is already handling this conversation.',
                        ];
                    }

                    if (
                        $conversation->mode
                        === 'live_waiting'
                    ) {
                        return [
                            'conversation' =>
                                $conversation,

                            'message' =>
                                null,

                            'already_requested' =>
                                true,

                            'response_message' =>
                                'A live agent has already been notified. Please wait a moment.',
                        ];
                    }

                    $conversation->update([
                        'mode' =>
                            'live_waiting',

                        /*
                         * Existing website live-chat assignment.
                         */
                        'assigned_agent_id' =>
                            null,

                        /*
                         * New common omnichannel assignment.
                         */
                        'assigned_user_id' =>
                            null,

                        'live_requested_at' =>
                            now(),

                        'live_started_at' =>
                            null,

                        'live_ended_at' =>
                            null,
                    ]);

                    /*
                     * System message remains local but now
                     * also includes omnichannel fields.
                     */
                    $systemMessage =
                        Message::create([
                            'conversation_id' =>
                                $conversation->id,

                            'channel_connection_id' =>
                                $conversation
                                    ->channel_connection_id,

                            'user_id' =>
                                null,

                            'sender_user_id' =>
                                null,

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
                                'Visitor requested a live agent.',
                        ]);

                    return [
                        'conversation' =>
                            $conversation->fresh(),

                        'message' =>
                            $systemMessage,

                        'already_requested' =>
                            false,

                        'response_message' =>
                            'A live agent has been notified. Please wait a moment.',
                    ];
                }
            );
=======
        $baseConversation =
            $this->websiteConversationResolver
                ->resolve(
                    $website,
                    $validated['visitor_id']
                );

        $result = DB::transaction(
            function () use (
                $baseConversation
            ): array {
                $conversation =
                    Conversation::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $baseConversation->id
                        );

                if ($conversation->mode === 'live') {
                    return [
                        'conversation' =>
                            $conversation,

                        'message' =>
                            null,

                        'already_requested' =>
                            true,

                        'response_message' =>
                            'A live agent is already handling this conversation.',
                    ];
                }

                if (
                    $conversation->mode
                    === 'live_waiting'
                ) {
                    return [
                        'conversation' =>
                            $conversation,

                        'message' =>
                            null,

                        'already_requested' =>
                            true,

                        'response_message' =>
                            'A live agent has already been notified. Please wait a moment.',
                    ];
                }

                $conversation->update([
                    'mode' =>
                        'live_waiting',

                    'assigned_agent_id' =>
                        null,

                    'live_requested_at' =>
                        now(),

                    'live_started_at' =>
                        null,

                    'live_ended_at' =>
                        null,
                ]);

                $systemMessage =
                    Message::create([
                        'conversation_id' =>
                            $conversation->id,

                        'channel_connection_id' =>
                            $conversation->channel_connection_id,

                        'user_id' =>
                            null,

                        'sender_user_id' =>
                            null,

                        'sender' =>
                            'system',

                        'role' =>
                            'assistant',

                        'direction' =>
                            'outbound',

                        'sender_type' =>
                            'system',

                        'message_type' =>
                            'text',

                        'status' =>
                            'sent',

                        'provider_status' =>
                            'sent',

                        'is_ai_generated' =>
                            false,

                        'is_system' =>
                            true,

                        'message' =>
                            'Visitor requested a live agent.',

                        'sent_at' =>
                            now(),

                        'payload' => [
                            'internal_event' =>
                                'live_agent_requested',
                        ],
                    ]);

                return [
                    'conversation' =>
                        $conversation->fresh(),

                    'message' =>
                        $systemMessage,

                    'already_requested' =>
                        false,

                    'response_message' =>
                        'A live agent has been notified. Please wait a moment.',
                ];
            }
        );
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        $conversation =
            $result['conversation'];

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Broadcast live-agent request
        |--------------------------------------------------------------------------
        */

        if (
            !$result['already_requested']
        ) {
=======
        if (!$result['already_requested']) {
            $systemMessage =
                $result['message'];

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            broadcast(
                new ConversationMessageCreated(
                    $systemMessage
                )
            );

<<<<<<< HEAD
            /*
             * Also publish to the new omnichannel inbox.
             */
            OmnichannelMessageChanged::dispatch(
                $result['message'],
                'created'
            );
=======
            try {
                OmnichannelMessageChanged::dispatch(
                    $systemMessage,
                    'created'
                );
            } catch (\Throwable $exception) {
                Log::warning(
                    'Live-agent system message omnichannel broadcast failed.',
                    [
                        'message_id' =>
                            $systemMessage->id,

                        'error' =>
                            $exception->getMessage(),
                    ]
                );
            }
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

            broadcast(
                new ConversationModeChanged(
                    $conversation
                )
            );

            broadcast(
                new LiveAgentRequested(
                    $conversation
                )
            );
        }

        return response()->json([
            'success' =>
                true,

            'message' =>
                $result[
                    'response_message'
                ],

            'conversation_id' =>
                $conversation->id,

            'conversation_channel' =>
                $this->conversationChannel(
                    $conversation
                ),

            'mode' =>
                $conversation->mode,

            'already_requested' =>
                $result[
                    'already_requested'
                ],
        ]);
    }

<<<<<<< HEAD
    /**
     * Find an existing website conversation without
     * creating one.
     */
    private function findWebsiteConversation(
        Website $website,
        string $visitorId
    ): ?Conversation {
        try {
            $connection =
                $this
                    ->websiteConversationResolver
                    ->connectionFor(
                        $website
                    );

            $externalThreadId =
                WebsiteIdentity::externalThreadId(
                    $website->id,
                    $visitorId
                );

            /*
             * Prefer omnichannel lookup.
             */
            $conversation =
                Conversation::query()
                    ->where(
                        'channel_connection_id',
                        $connection->id
                    )
                    ->where(
                        'external_thread_id',
                        $externalThreadId
                    )
                    ->first();

            if ($conversation) {
                return $conversation;
            }
        } catch (RuntimeException) {
            /*
             * During deployment/backfill, fall through
             * and attempt the legacy lookup.
             */
        }

        /*
         * Legacy website lookup.
         */
        return Conversation::query()
            ->where(
                'website_id',
                $website->id
            )
            ->where(
                'visitor_id',
                $visitorId
            )
            ->orderBy('id')
            ->first();
=======

    private function resolveWebsite(Request $request)
    {
        return $request->website
            ?? $request->attributes->get('website');
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    /**
     * Website is attached to the request by
     * the existing embed-token middleware.
     */
    private function resolveWebsite(
        Request $request
    ): ?Website {
        return
            $request->website
            ??
            $request
                ->attributes
                ->get(
                    'website'
                );
    }

    /**
     * Existing website Reverb channel.
     */
    private function conversationChannel(
        Conversation $conversation
    ): ?string {
        if (
            !$conversation->realtime_token
        ) {
            return null;
        }

        return
            'conversation.'
            . $conversation->realtime_token;
    }

    /**
     * Preserve the JSON structure expected by
     * the existing website widget.
     */
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