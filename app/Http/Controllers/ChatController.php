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
        $request->validate([
            'message' => 'required|string',
            'visitor_id' => 'required|string',
        ]);

        $website = $request->website;

        $conversation = Conversation::firstOrCreate(
            [
                'website_id' => $website->id,
                'visitor_id' => $request->visitor_id
            ],
            [
                'status' => 'active'
            ]
        );

        Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'visitor',
            'message' => $request->message
        ]);

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

        Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'visitor',
            'message' => $request->message
        ]);

        $aiText = $brain->analyze(
            $request->message,
            $website,
            $history
        );

        return response()->json([
            'reply' => $aiText,
            'conversation_id' => $conversation->id
        ]);
    }
}
