<?PHP 
namespace App\Services;
use OpenAI\Laravel\Facades\OpenAI;

class SalesBrainService
{
    public function analyze($message, $website, $history = [])
    {
        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt($website)
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ]);

        return $response->output[0]->content[0]->text ?? '';
    }

    private function systemPrompt($website)
    {
        return "
            You are an AI SALES AGENT for {$website->name}.

            Your job:
            1. Understand user intent
            2. Identify product/service need
            3. Recommend relevant pages
            4. Collect lead information step by step
            5. Do NOT hallucinate information
            6. Only use website context when provided

            Behavior rules:
            - Be human like a sales assistant
            - Ask questions when unclear
            - Push toward conversion (lead capture)
            - Never answer outside business scope
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