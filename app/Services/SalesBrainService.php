<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Website;
use App\Models\Lead;

class SalesBrainService
{
    public function __construct(
        protected KnowledgeSearchService $knowledgeSearch
    ) {}

    public function analyze(
        string $message,
        Website $website,
        array $history = [],
        ?Lead $lead = null,
        ?string $leadStage = null,
        ?string $nextLeadQuestion = null
    ): string {
        $knowledge = $this->knowledgeSearch->search($website, $message, 5);

        $knowledgeText = collect($knowledge)->map(function ($item, $index) {
            return "SOURCE " . ($index + 1) . ":\n"
                . "Title: " . ($item['title'] ?? 'Untitled') . "\n"
                . "URL: " . $item['url'] . "\n"
                . "Content: " . $item['text'];
        })->implode("\n\n");

        $leadContext = $this->buildLeadContext(
            $lead,
            $leadStage,
            $nextLeadQuestion
        );

        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => array_merge([
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt(
                        $website,
                        $knowledgeText,
                        $leadContext
                    ),
                ],
            ], $history, [
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ]),
        ]);

        return $response->output[0]->content[0]->text ?? '';
    }

    private function buildLeadContext(
        ?Lead $lead,
        ?string $leadStage,
        ?string $nextLeadQuestion
    ): string {
        if (!$lead) {
            return "
            Lead Status:
            No lead has been created yet.

            Instruction:
            If the visitor shows buying intent, naturally begin lead qualification.
            ";
                    }

                    return "
            Lead Status:
            Lead ID: {$lead->id}
            Name: " . ($lead->name ?? 'Not collected') . "
            Email: " . ($lead->email ?? 'Not collected') . "
            Phone: " . ($lead->phone ?? 'Not collected') . "
            Country: " . ($lead->country ?? 'Not collected') . "
            Preferred Contact Time: " . ($lead->preferred_contact_time ?? 'Not collected') . "
            Product Interest: " . ($lead->product_interest ?? 'Not collected') . "
            Lead Score: {$lead->lead_score}
            Lead Stage: " . ($leadStage ?? 'unknown') . "

            Next Lead Question:
            " . ($nextLeadQuestion ?? 'No specific lead question required') . "
            ";
                }

                private function systemPrompt(
                    Website $website,
                    string $knowledgeText,
                    string $leadContext
                ): string {
                    return "
            You are an AI sales agent for {$website->name}.

            Use ONLY the website knowledge below when answering product, service, pricing, blog, whitepaper, or company-specific questions.

            If the answer is not available in the knowledge, say you do not have enough information and ask whether a human team member should contact the visitor.

            Your goals:
            1. Understand the visitor's need.
            2. Recommend relevant products, services, blog posts, or whitepapers from the provided sources.
            3. Include relevant URLs when useful.
            4. Collect lead information step by step.
            5. Never ask for all contact details at once.
            6. Do not repeatedly ask for information already collected.
            7. If there is a Next Lead Question, ask it naturally at the end of the response.
            8. Be concise, helpful, professional, and sales-focused.
            9. Never invent facts not present in the provided knowledge.

            {$leadContext}

            Website Knowledge:
            {$knowledgeText}
            ";
                }
}