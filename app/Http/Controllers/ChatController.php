<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Website;
use App\Models\Conversation;
use App\Models\Message;
use OpenAI\Laravel\Facades\OpenAI;
use App\Services\SalesBrainService;

class ChatController extends Controller
{
    public function message(Request $request, SalesBrainService $brain)
    {
        // 1. Validate input
        $request->validate([
            'message' => 'required|string',
            'visitor_id' => 'required|string',
        ]);

        // 2. Get website from middleware
        $website = $request->website;

        // 3. Find or create conversation
        $conversation = Conversation::firstOrCreate(
            [
                'website_id' => $website->id,
                'visitor_id' => $request->visitor_id
            ],
            [
                'status' => 'active'
            ]
        );

        // 4. Save visitor message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'visitor',
            'message' => $request->message
        ]);

        // 5. Fetch recent messages for context
        $history = Message::where('conversation_id', $conversation->id)
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(function ($msg) {
                return [
                    'role' => $msg->sender === 'visitor' ? 'user' : 'assistant',
                    'content' => $msg->message
                ];
            })
            ->values()
            ->toArray();

        // 6. Call OpenAI
        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => array_merge([
                [
                    'role' => 'system',
                    'content' => 'You are a helpful AI sales assistant.'
                ]
            ], $history),
        ]);

        $aiText = $brain->analyze(
            $request->message,
            $website,
            $history
        );

        // 7. Save AI response
        Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'ai',
            'message' => $aiText
        ]);

        // 8. Return response
        return response()->json([
            'reply' => $aiText,
            'conversation_id' => $conversation->id
        ]);
    }
}
