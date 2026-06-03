<?PHP 
namespace App\Services;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Website;

class SalesBrainService
{

    public function __construct(
        protected KnowledgeSearchService $knowledgeSearch
    ) {}
    public function analyze(string $message, Website $website, array $history = []): string
    {
        $knowledge = $this->knowledgeSearch->search($website, $message, 5);

        $knowledgeText = collect($knowledge)->map(function ($item, $index) {
            return "SOURCE " . ($index + 1) . ":\n"
                . "Title: " . ($item['title'] ?? 'Untitled') . "\n"
                . "URL: " . $item['url'] . "\n"
                . "Content: " . $item['text'];
        })->implode("\n\n");

        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => array_merge([
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt($website, $knowledgeText),
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

    private function systemPrompt(Website $website, string $knowledgeText): string
        {
            return "
            You are an AI sales agent for {$website->name}.

            Use ONLY the website knowledge below when answering product, service, pricing, blog, whitepaper, or company-specific questions.

            If the answer is not available in the knowledge, say you do not have enough information and ask the visitor whether they want a human agent to contact them.

            Your goals:
            1. Understand the visitor's need.
            2. Recommend relevant products, services, blog posts, or whitepapers from the provided sources.
            3. Include relevant URLs when useful.
            4. Collect lead information step by step: name, email, phone, country, and preferred contact time.
            5. Be concise, professional, and sales-focused.
            6. Never invent facts not present in the provided knowledge.

            Website Knowledge:
            {$knowledgeText}
            ";
        }

    public function detectIntent($message)
    {
        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => [
                [
                    'role' => 'system',
                    'content' => 'Classify user intent into:
                        - product_inquiry
                        - pricing
                        - support
                        - general_question
                        - lead_request'
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ]);

        return $response->output[0]->content[0]->text ?? 'general_question';
    }
    public function extractLeadData($message)
    {
        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => [
                [
                    'role' => 'system',
                    'content' => 'Extract structured data:
                    - name
                    - email
                    - phone
                    - country
                    Return JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ]);

        return json_decode(
            $response->output[0]->content[0]->text ?? '{}',
            true
        );
    }
}