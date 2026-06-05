<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Website;
use App\Models\Conversation;
use OpenAI\Laravel\Facades\OpenAI;

class LeadCaptureService
{
    public function __construct(
        protected LeadScoringService $leadScoringService
    ) {}

    public function extractLeadData(string $message): array
    {
        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => [
                [
                    'role' => 'system',
                    'content' => '
                    Extract lead information from the user message.

                    Return JSON only.

                    Allowed fields:
                    {
                    "name": null|string,
                    "email": null|string,
                    "phone": null|string,
                    "country": null|string,
                    "preferred_contact_time": null|string,
                    "product_interest": null|string,
                    "has_buying_intent": true|false
                    }

                    Rules:
                    - Do not guess.
                    - If a field is not clearly provided, return null.
                    - product_interest should be short, for example: "CRM", "SEO", "web design", "hosting", "AI chatbot".
                    - has_buying_intent is true if the user appears interested in buying, requesting, comparing, pricing, booking, demo, consultation, or service information.
                    '
                ],
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
        ]);

        $text = $response->output[0]->content[0]->text ?? '{}';

        return $this->safeJsonDecode($text);
    }

    private function safeJsonDecode(string $text): array
    {
        $text = trim($text);

        // Remove markdown code block if OpenAI returns ```json
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/^```\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function shouldCreateLead(array $data): bool
    {
        return !empty($data['name'])
            || !empty($data['email'])
            || !empty($data['phone'])
            || !empty($data['product_interest'])
            || !empty($data['has_buying_intent']);
    }

    public function getOrCreateLead(
        Website $website,
        Conversation $conversation,
        array $data
    ): ?Lead {
        if (!$this->shouldCreateLead($data)) {
            return $conversation->lead;
        }

        if ($conversation->lead_id) {
            return Lead::find($conversation->lead_id);
        }

        $lead = Lead::create([
            'tenant_id' => $website->tenant_id,
            'website_id' => $website->id,
            'conversation_id' => $conversation->id,
        ]);

        $conversation->lead_id = $lead->id;
        $conversation->save();

        return $lead;
    }

    public function updateLeadFromData(?Lead $lead, array $data): ?Lead
    {
        if (!$lead) {
            return null;
        }

        $update = [];

        if (!empty($data['name']) && empty($lead->name)) {
            $update['name'] = $data['name'];
        }

        if (!empty($data['email']) && empty($lead->email)) {
            $update['email'] = $data['email'];
        }

        if (!empty($data['phone']) && empty($lead->phone)) {
            $update['phone'] = $data['phone'];
        }

        if (!empty($data['country']) && empty($lead->country)) {
            $update['country'] = $data['country'];
        }

        if (!empty($data['preferred_contact_time']) && empty($lead->preferred_contact_time)) {
            $update['preferred_contact_time'] = $data['preferred_contact_time'];
        }

        if (!empty($data['product_interest']) && empty($lead->product_interest)) {
            $update['product_interest'] = $data['product_interest'];
        }

        if (!empty($update)) {
            $lead->update($update);
        }

        return $this->leadScoringService->updateScore($lead->fresh());
    }

    public function updateConversationStage(Conversation $conversation, ?Lead $lead): void
    {
        if (!$lead) {
            $conversation->lead_stage = 'discovery';
            $conversation->save();
            return;
        }

        if (empty($lead->product_interest)) {
            $conversation->lead_stage = 'product_interest_capture';
        } elseif (empty($lead->name)) {
            $conversation->lead_stage = 'name_capture';
        } elseif (empty($lead->email)) {
            $conversation->lead_stage = 'email_capture';
        } elseif (empty($lead->phone)) {
            $conversation->lead_stage = 'phone_capture';
        } elseif (empty($lead->country)) {
            $conversation->lead_stage = 'country_capture';
        } elseif (empty($lead->preferred_contact_time)) {
            $conversation->lead_stage = 'contact_time_capture';
        } else {
            $conversation->lead_stage = 'qualified';
        }

        $conversation->save();
    }

    public function getNextLeadQuestion(?Lead $lead, Conversation $conversation): ?string
    {
        if (!$lead) {
            return null;
        }

        return match ($conversation->lead_stage) {
            'product_interest_capture' => 'What product or service are you most interested in?',
            'name_capture' => 'May I have your name so our team can assist you better?',
            'email_capture' => 'What is the best email address to send you the details?',
            'phone_capture' => 'Could you share a contact number in case our team needs to reach you?',
            'country_capture' => 'Which country are you located in?',
            'contact_time_capture' => 'What would be a good time for our team to contact you?',
            'qualified' => 'Thank you. I have the details needed. Our team can follow up with you.',
            default => null,
        };
    }

    public function processMessage(
        Website $website,
        Conversation $conversation,
        string $message
    ): array {
        $data = $this->extractLeadData($message);

        $lead = $this->getOrCreateLead(
            $website,
            $conversation,
            $data
        );

        $lead = $this->updateLeadFromData(
            $lead,
            $data
        );

        $this->updateConversationStage(
            $conversation,
            $lead
        );

        $conversation->refresh();

        return [
            'lead' => $lead,
            'extracted_data' => $data,
            'lead_stage' => $conversation->lead_stage,
            'next_question' => $this->getNextLeadQuestion($lead, $conversation),
        ];
    }
}