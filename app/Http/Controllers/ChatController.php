<?php

namespace App\Http\Controllers;

use App\Events\ConversationMessageCreated;
use App\Events\ConversationModeChanged;
use App\Events\LiveAgentRequested;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AgentAvailabilityService;
use App\Services\LeadCaptureService;
use App\Services\SalesBrainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Knowledge\KnowledgeContextBuilder;
use App\Services\Knowledge\KnowledgeRetriever;

class ChatController extends Controller
{

    public function __construct(
    private readonly KnowledgeRetriever $knowledgeRetriever,
    private readonly KnowledgeContextBuilder $contextBuilder
    ) {
    }

    public function message(
        Request $request,
        SalesBrainService $brain,
        LeadCaptureService $leadCaptureService,
        KnowledgeRetriever $knowledgeRetriever,
        KnowledgeContextBuilder $knowledgeContextBuilder
    ): JsonResponse {
        /*
        * Validate the visitor message.
        */
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
        ]);

        /*
        * Resolve the website using your existing embed-token logic.
        */
        $website = $this->resolveWebsite($request);

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved from the embed token.',
            ], 404);
        }

        /*
        * Find the existing conversation or create one.
        */
        $conversation = Conversation::firstOrCreate(
            [
                'website_id' => $website->id,
                'visitor_id' => $validated['visitor_id'],
            ],
            [
                'status' => 'active',
                'mode' => 'ai',
                'lead_stage' => 'discovery',
            ]
        );

        /*
        * Older conversation records may not have a mode.
        */
        if (!$conversation->mode) {
            $conversation->forceFill([
                'mode' => 'ai',
            ])->save();
        }

        /*
        * Load previous conversation history.
        *
        * This happens before saving the current message so the
        * current visitor message is not sent to the AI twice.
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

                    'content' => $message->message,
                ];
            })
            ->values()
            ->toArray();

        /*
        * Save the current visitor message.
        */
        $visitorMessage = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'sender' => 'visitor',
            'is_system' => false,
            'message' => trim(
                $validated['message']
            ),
        ]);

        $conversation->touch();

        broadcast(
            new ConversationMessageCreated(
                $visitorMessage
            )
        );

        /*
        * Refresh in case conversation mode was changed
        * by a live agent or another process.
        */
        $conversation->refresh();

        /*
        * Do not generate an AI response during live-agent mode.
        */
        if (
            in_array(
                $conversation->mode,
                ['live_waiting', 'live'],
                true
            )
        ) {
            return response()->json([
                'success' => true,
                'reply' => null,
                'reply_message' => null,
                'mode' => $conversation->mode,

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

        /*
        * Process lead capture information.
        */
        $leadResult =
            $leadCaptureService->processMessage(
                $website,
                $conversation,
                $validated['message']
            );

        $lead = $leadResult['lead'] ?? null;

        $leadStage =
            $leadResult['lead_stage']
            ?? $conversation->lead_stage
            ?? 'discovery';

        $nextLeadQuestion =
            $leadResult['next_question']
            ?? null;

        /*
        * Retrieve matching chunks from:
        *
        * 1. Existing crawled URLs
        * 2. Uploaded documents
        *
        * IMPORTANT:
        * Use $knowledgeRetriever, not $this->knowledgeRetriever,
        * because it was injected into this method.
        */
        $knowledgeResults = [];

        $knowledgeContext =
            'No relevant knowledge was found for this question.';

        try {
            $knowledgeResults =
                $knowledgeRetriever->retrieve(
                    $website,
                    $validated['message']
                );

            /*
            * Convert the selected chunks into readable text
            * that can be included in the AI system prompt.
            */
            $knowledgeContext =
                $knowledgeContextBuilder->build(
                    $knowledgeResults
                );
        } catch (\Throwable $exception) {
            /*
            * Retrieval failure should not completely stop
            * the chatbot.
            */
            \Log::error(
                'Knowledge retrieval failed.',
                [
                    'website_id' => $website->id,

                    'conversation_id' =>
                        $conversation->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }

        /*
        * Generate the AI response.
        *
        * The final parameter is the newly retrieved
        * knowledge context.
        */
        $aiText = $brain->analyze(
            $validated['message'],
            $website,
            $history,
            $lead,
            $leadStage,
            $nextLeadQuestion,
            $knowledgeContext
        );

        $aiText = trim((string) $aiText);

        if ($aiText === '') {
            $aiText =
                'Sorry, I could not generate a response right now.';
        }

        /*
        * Save the AI message.
        */
        $aiMessage = Message::create([
            'conversation_id' =>
                $conversation->id,

            'user_id' => null,
            'sender' => 'ai',
            'is_system' => false,
            'message' => $aiText,
        ]);

        $conversation->touch();

        broadcast(
            new ConversationMessageCreated(
                $aiMessage
            )
        );

        /*
        * Return the response to the chat widget.
        */
        return response()->json([
            'success' => true,

            'reply' => $aiMessage->message,

            'reply_message' =>
                $this->formatMessage(
                    $aiMessage
                ),

            'mode' =>
                $conversation->mode ?: 'ai',

            'conversation_id' =>
                $conversation->id,

            'conversation_channel' =>
                $this->conversationChannel(
                    $conversation
                ),

            'lead' => $lead
                ? [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'country' => $lead->country,

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

            'lead_stage' => $leadStage,
        ]);
    }

  
    public function config(
        Request $request,
        AgentAvailabilityService $agentAvailability
    ): JsonResponse {
        $website = $this->resolveWebsite($request);

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved from the embed token.',
            ], 404);
        }

        $themes = config('chatbot.themes', []);

        $defaultThemeKey = config(
            'chatbot.default_theme',
            'blue'
        );

        $fallbackTheme = [
            'label' => 'Blue',
            'primary' => '#2563eb',
            'secondary' => '#eff6ff',
            'text' => '#ffffff',
        ];

        $themeKey = $website->chatbot_theme
            ?: $defaultThemeKey;

        $theme = $themes[$themeKey]
            ?? $themes[$defaultThemeKey]
            ?? $fallbackTheme;

        $realtimeKey = config('chatbot.realtime.key');
        $realtimeHost = config('chatbot.realtime.host');

        return response()->json([
            'website_id' => $website->id,

            'chatbot_name' =>
                $website->chatbot_name
                ?: $website->name . ' Assistant',

            'theme' => [
                'key' => $themeKey,

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

            'avatar_url' => $website->chatbot_avatar
                ? asset(
                    'storage/' .
                    ltrim($website->chatbot_avatar, '/')
                )
                : null,

            'live_agent_available' =>
                $agentAvailability->hasOnlineAgent($website),

            'realtime' => [
                'enabled' =>
                    filled($realtimeKey) &&
                    filled($realtimeHost) &&
                    filled($website->realtime_token),

                'key' => $realtimeKey,
                'host' => $realtimeHost,

                'port' => (int) config(
                    'chatbot.realtime.port',
                    443
                ),

                'scheme' => config(
                    'chatbot.realtime.scheme',
                    'https'
                ),

                'website_channel' =>
                    filled($website->realtime_token)
                        ? 'website.' .
                            $website->realtime_token
                        : null,
            ],
        ]);
    }

  
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
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

        $website = $this->resolveWebsite($request);

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved from the embed token.',
            ], 404);
        }

        $limit = (int) (
            $validated['limit']
            ?? 50
        );

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where(
                'visitor_id',
                $validated['visitor_id']
            )
            ->first();

        if (!$conversation) {
            return response()->json([
                'conversation_id' => null,
                'conversation_channel' => null,
                'mode' => 'ai',
                'messages' => [],
            ]);
        }

        $messages = $conversation
            ->messages()
            ->with('user')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(function (Message $message): array {
                return $this->formatMessage($message);
            });

        return response()->json([
            'conversation_id' => $conversation->id,

            'conversation_channel' =>
                $this->conversationChannel($conversation),

            'mode' => $conversation->mode ?: 'ai',

            'messages' => $messages,
        ]);
    }

 
    public function requestLiveAgent(
        Request $request,
        AgentAvailabilityService $agentAvailability
    ): JsonResponse {
        $validated = $request->validate([
            'visitor_id' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $website = $this->resolveWebsite($request);

        if (!$website) {
            return response()->json([
                'message' =>
                    'Website could not be resolved.',
            ], 404);
        }

        if (
            !$agentAvailability->hasOnlineAgent(
                $website
            )
        ) {
            return response()->json([
                'message' =>
                    'No live agent is currently available.',

                'available' => false,
            ], 409);
        }

        $result = DB::transaction(function () use (
            $website,
            $validated
        ): array {
            $conversation = Conversation::query()
                ->firstOrCreate(
                    [
                        'website_id' => $website->id,

                        'visitor_id' =>
                            $validated['visitor_id'],
                    ],
                    [
                        'status' => 'active',
                        'mode' => 'ai',
                        'lead_stage' => 'discovery',
                    ]
                );

            $conversation = Conversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            if ($conversation->mode === 'live') {
                return [
                    'conversation' => $conversation,
                    'message' => null,
                    'already_requested' => true,
                    'response_message' =>
                        'A live agent is already handling this conversation.',
                ];
            }

            if (
                $conversation->mode ===
                'live_waiting'
            ) {
                return [
                    'conversation' => $conversation,
                    'message' => null,
                    'already_requested' => true,
                    'response_message' =>
                        'A live agent has already been notified. Please wait a moment.',
                ];
            }

            $conversation->update([
                'mode' => 'live_waiting',
                'assigned_agent_id' => null,
                'live_requested_at' => now(),
                'live_started_at' => null,
                'live_ended_at' => null,
            ]);

            $systemMessage = Message::create([
                'conversation_id' =>
                    $conversation->id,

                'user_id' => null,
                'sender' => 'system',
                'is_system' => true,

                'message' =>
                    'Visitor requested a live agent.',
            ]);

            return [
                'conversation' =>
                    $conversation->fresh(),

                'message' => $systemMessage,

                'already_requested' => false,

                'response_message' =>
                    'A live agent has been notified. Please wait a moment.',
            ];
        });

        $conversation =
            $result['conversation'];

        if (!$result['already_requested']) {
            broadcast(
                new ConversationMessageCreated(
                    $result['message']
                )
            );

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
            'success' => true,

            'message' =>
                $result['response_message'],

            'conversation_id' =>
                $conversation->id,

            'conversation_channel' =>
                $this->conversationChannel(
                    $conversation
                ),

            'mode' =>
                $conversation->mode,

            'already_requested' =>
                $result['already_requested'],
        ]);
    }

    private function resolveWebsite(Request $request)
    {
        return $request->website
            ?? $request->attributes->get('website');
    }


    private function conversationChannel(
        Conversation $conversation
    ): ?string {
        if (!$conversation->realtime_token) {
            return null;
        }

        return 'conversation.' .
            $conversation->realtime_token;
    }

    private function formatMessage(
        Message $message
    ): array {
        $message->loadMissing('user');

        return [
            'id' => $message->id,

            'conversation_id' =>
                $message->conversation_id,

            'sender' => $message->sender,

            'message' => $message->message,

            'is_system' =>
                (bool) $message->is_system,

            'agent_name' =>
                $message->user?->name,

            'created_at' =>
                $message->created_at
                    ?->toISOString(),
        ];
    }
    
}