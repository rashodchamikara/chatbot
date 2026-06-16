<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\SalesBrainService;
use App\Services\LeadCaptureService;


class ChatController extends Controller
{
    public function message(
        Request $request,
        SalesBrainService $brain,
        LeadCaptureService $leadCaptureService
    ) {
        $request->validate([
            'message' => 'required|string|max:5000',
            'visitor_id' => 'required|string|max:255',
        ]);

        $website = $request->website;

        $conversation = Conversation::firstOrCreate(
            [
                'website_id' => $website->id,
                'visitor_id' => $request->visitor_id,
            ],
            [
                'status' => 'active',
                'lead_stage' => 'discovery',
            ]
        );

        $history = Message::where('conversation_id', $conversation->id)
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(function ($msg) {
                return [
                    'role' => $msg->sender === 'visitor' ? 'user' : 'assistant',
                    'content' => $msg->message,
                ];
            })
            ->values()
            ->toArray();

        $visitorMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'visitor',
            'message' => $request->message,
        ]);

        broadcast(new \App\Events\ConversationMessageCreated($visitorMessage));

        if (in_array($conversation->mode, ['live_waiting', 'live'])) {
            return response()->json([
                'reply' => null,
                'mode' => $conversation->mode,
                'conversation_id' => $conversation->id,
                'conversation_channel' => 'conversation.' . $conversation->realtime_token,
                'message' => 'Message sent to live agent.',
            ]);
        }

        $leadResult = $leadCaptureService->processMessage(
            $website,
            $conversation,
            $request->message
        );

        $lead = $leadResult['lead'];
        $leadStage = $leadResult['lead_stage'];
        $nextLeadQuestion = $leadResult['next_question'];

        $aiText = $brain->analyze(
            $request->message,
            $website,
            $history,
            $lead,
            $leadStage,
            $nextLeadQuestion
        );

        $aiMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'ai',
            'is_system' => false,
            'message' => $aiText,
        ]);

        broadcast(
            new \App\Events\ConversationMessageCreated(
                $aiMessage
            )
        );
        $conversation->touch();
        return response()->json([
            'reply' => $aiText,
            'conversation_id' => $conversation->id,
            'lead' => $lead ? [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'country' => $lead->country,
                'preferred_contact_time' => $lead->preferred_contact_time,
                'product_interest' => $lead->product_interest,
                'lead_score' => $lead->lead_score,
                'status' => $lead->status,
            ] : null,
            'lead_stage' => $leadStage,
        ]);
    }
    public function config(Request $request, \App\Services\AgentAvailabilityService $agentAvailability)
    {
        $website = $request->website ?? $request->attributes->get('website');

        if (!$website) {
            return response()->json([
                'message' => 'Website could not be resolved from embed token.',
            ], 404);
        }

        $themes = config('chatbot.themes', []);
        $defaultThemeKey = config('chatbot.default_theme', 'blue');

        $fallbackTheme = [
            'label' => 'Blue',
            'primary' => '#2563eb',
            'secondary' => '#eff6ff',
            'text' => '#ffffff',
        ];

        $themeKey = $website->chatbot_theme ?: $defaultThemeKey;

        $theme = $themes[$themeKey]
            ?? $themes[$defaultThemeKey]
            ?? $fallbackTheme;

        return response()->json([
            'website_id' => $website->id,

            'chatbot_name' => $website->chatbot_name
                ?: $website->name . ' Assistant',

            'theme' => [
                'key' => $themeKey,
                'primary' => $theme['primary'] ?? $fallbackTheme['primary'],
                'secondary' => $theme['secondary'] ?? $fallbackTheme['secondary'],
                'text' => $theme['text'] ?? $fallbackTheme['text'],
            ],

            'avatar_url' => $website->chatbot_avatar
                ? asset('storage/' . $website->chatbot_avatar)
                : null,

            'live_agent_available' => $agentAvailability->hasOnlineAgent($website),

            'realtime' => [
                'enabled' => true,
                'key' => config('chatbot.realtime.key'),
                'host' => config('chatbot.realtime.host'),
                'port' => config('chatbot.realtime.port'),
                'scheme' => config('chatbot.realtime.scheme'),
                'website_channel' => 'website.' . $website->realtime_token,
            ],
        ]);
    }
    public function history(Request $request)
    {
        $request->validate([
            'visitor_id' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $website = $request->website ?? $request->attributes->get('website');

        if (!$website) {
            return response()->json([
                'message' => 'Website could not be resolved from embed token.',
            ], 404);
        }

        $limit = (int) $request->input('limit', 50);

        $conversation = \App\Models\Conversation::where('website_id', $website->id)
            ->where('visitor_id', $request->visitor_id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'conversation_id' => null,
                'conversation_channel' => null,
                'mode' => 'ai',
                'messages' => [],
            ]);
        }

        $messages = $conversation->messages()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'message' => $message->message,
                    'is_system' => (bool) $message->is_system,
                    'created_at' => optional($message->created_at)->toDateTimeString(),
                ];
            });

        return response()->json([
            'conversation_id' => $conversation->id,
            'conversation_channel' => 'conversation.' . $conversation->realtime_token,
            'mode' => $conversation->mode,
            'messages' => $messages,
        ]);
    }
    public function requestLiveAgent(
        Request $request,
        \App\Services\AgentAvailabilityService $agentAvailability
    ) {
        $request->validate([
            'visitor_id' => ['required', 'string', 'max:255'],
        ]);

        $website = $request->website ?? $request->attributes->get('website');

        if (!$website) {
            return response()->json([
                'message' => 'Website could not be resolved.',
            ], 404);
        }

        if (!$agentAvailability->hasOnlineAgent($website)) {
            return response()->json([
                'message' => 'No live agent is currently available.',
                'available' => false,
            ], 409);
        }

        $conversation = \App\Models\Conversation::firstOrCreate(
            [
                'website_id' => $website->id,
                'visitor_id' => $request->visitor_id,
            ],
            [
                'status' => 'active',
                'mode' => 'ai',
                'lead_stage' => 'discovery',
            ]
        );

        $conversation->update([
            'mode' => 'live_waiting',
            'live_requested_at' => now(),
            'live_ended_at' => null,
        ]);

        $message = \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'system',
            'is_system' => true,
            'message' => 'Visitor requested a live agent.',
        ]);

        broadcast(new \App\Events\ConversationMessageCreated($message));
        broadcast(new \App\Events\ConversationModeChanged($conversation->fresh()));
        broadcast(new \App\Events\LiveAgentRequested($conversation->fresh()));

        return response()->json([
            'message' => 'A live agent has been notified. Please wait a moment.',
            'conversation_id' => $conversation->id,
            'conversation_channel' => 'conversation.' . $conversation->realtime_token,
            'mode' => $conversation->mode,
        ]);
    }
}