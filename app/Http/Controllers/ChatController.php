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

        Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'visitor',
            'message' => $request->message,
        ]);

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

        Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'ai',
            'message' => $aiText,
        ]);

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
}