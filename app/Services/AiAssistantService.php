<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1/chat/completions';
    protected $contextService;

    public function __construct(AccountingContextService $contextService)
    {
        $this->apiKey = config('services.openai.key');
        $this->contextService = $contextService;
    }

    /**
     * Record a user message in the session.
     */
    public function addUserMessage(AiChatSession $session, string $message)
    {
        $session->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $session->update(['last_active_at' => now()]);
    }

    /**
     * Generate an AI response based on the current session history.
     */
    public function generateAnalysis(AiChatSession $session)
    {
        $messages = $this->prepareMessages($session);

        try {
            Log::info('Initiating Professional AI Analysis...', ['session_id' => $session->id]);
            
            $response = Http::withToken($this->apiKey)
                ->withoutVerifying()
                ->timeout(60)
                ->post($this->baseUrl, [
                    'model' => 'gpt-4o', 
                    'messages' => $messages,
                    'temperature' => 0.2, // Deterministic for professional results
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API Failure', ['status' => $response->status(), 'body' => $response->body()]);
                return 'SYSTEM_ERROR: Communication with the financial analysis engine failed.';
            }

            $rawContent = $response->json('choices.0.message.content');
            $structuredData = json_decode($rawContent, true);

            if (!$structuredData || !isset($structuredData['confidence_level'])) {
                throw new \Exception('AI response failed structural validation.');
            }

            Log::info('AI_ANALYSIS_SUCCESS', [
                'confidence' => $structuredData['confidence_level'],
                'session_id' => $session->id
            ]);

            // Save assistant response
            $session->messages()->create([
                'role' => 'assistant',
                'content' => $rawContent, // Save the full JSON
                'metadata' => $structuredData,
            ]);

            return $rawContent;

        } catch (\Exception $e) {
            Log::error('AI Analysis Exception', ['message' => $e->getMessage()]);
            return json_encode([
                'summary' => 'An unexpected error occurred during financial analysis.',
                'key_metrics' => [],
                'insights' => [],
                'risks' => ['Analysis Engine Failure'],
                'recommendations' => ['Please contact system administrator.'],
                'confidence_level' => 'Low'
            ]);
        }
    }

    protected function prepareMessages(AiChatSession $session)
    {
        $systemPrompt = $this->getSystemPrompt();
        
        // Get the latest user message to explicitly inject as the "Active Task"
        $latestUserMessage = $session->messages()
            ->where('role', 'user')
            ->latest()
            ->first();
            
        $userQuery = $latestUserMessage ? $latestUserMessage->content : 'Provide a general financial status update.';

        // INTENT CLASSIFICATION LAYER
        // We determine if the user explicitly wants a specific report.
        $classifier = new \App\Services\Ai\IntentClassifierService();
        $intentData = $classifier->classify($userQuery);
        $intent = $intentData['intent'];

        if ($intent !== \App\Services\Ai\IntentClassifierService::INTENT_UNKNOWN) {
            try {
                $reportHandler = \App\Services\Ai\Reports\ReportFactory::make($intent);
                $reportData = $reportHandler->generate($intentData);
                
                $context = "ACTIVE REPORT CONTEXT:\n" . json_encode($reportData, JSON_PRETTY_PRINT);
                $systemPrompt .= "\n\nCRITICAL: The user has requested a specific report (" . $reportData['report_name'] . "). Use the 'ACTIVE REPORT CONTEXT' provided below to answer. Do NOT use generic data.";
            } catch (\Exception $e) {
                // Fallback if handler fails
                Log::warning('Report Generation Failed: ' . $e->getMessage());
                $context = $this->contextService->getFullContextString();
            }
        } else {
            // Default: Use full context for general questions
            $context = $this->contextService->getFullContextString();
        }

        // Fix: Get the *latest* 10 messages, then reverse them to chronological order
        $history = $session->messages()
            ->latest() // Order by created_at desc
            ->take(10) // Limit context window
            ->get()
            ->reverse() // Reverse to asc for the API
            ->values()
            ->map(function ($msg) {
                $content = $msg->content;
                
                // Optimization: If role is assistant and content is JSON, extracting only the summary 
                // prevents the AI from getting stuck in a "repetition loop" of generating the same large JSON.
                if ($msg->role === 'assistant') {
                    $data = json_decode($content, true);
                    if ($data && isset($data['summary'])) {
                        // We replace the full JSON with just the summary context for the AI's memory
                        $content = "PREVIOUS REPORT SUMMARY: (" . ($data['confidence_level'] ?? 'N/A') . ") " . $data['summary'];
                    }
                }
                
                return ['role' => $msg->role, 'content' => $content];
            })
            ->toArray();

        // Construct the final payload with explicit "Active Task" instruction
        $payload = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            [['role' => 'system', 'content' => "CURRENT DATA CONTEXT:\n$context"]],
            $history,
            [['role' => 'system', 'content' => "IMPORTANT: The user has just asked: \"$userQuery\". Your response MUST specifically answer this question in the 'summary' field."]]
        );

        Log::debug('AI Request Payload', ['message_count' => count($payload), 'last_message' => end($payload), 'intent' => $intent]);

        return $payload;
    }

    protected function getSystemPrompt()
    {
        return "You are a Senior Chartered Accountant and Financial Analyst.

STRICT RULES:
- Follow IFRS-aligned accounting reasoning.
- Do NOT perform calculations; rely ONLY on provided figures from the CURRENT DATA CONTEXT.
- Do NOT guess missing data. Explicitly flag missing or incomplete information.
- Use formal, professional, audit-ready language.
- Identify risks, inefficiencies, and compliance concerns.
- Provide actionable, business-focused recommendations.
- Do not include emojis, casual tone, or conversational language.
- Respond ONLY in valid JSON following the schema below.
- IMPORTANT: The 'summary' field MUST directly answer the user's specific query if one is provided. Do not just provide a generic status update unless asked for one.

OUTPUT SCHEMA:
{
  \"summary\": \"Executive summary answering the user's specific query or providing financial status (string)\",
  \"key_metrics\": {
    \"total_income\": number,
    \"total_expenses\": number,
    \"net_profit\": number,
    \"profit_margin_percent\": number,
    \"roi_percent\": number,
    \"margin_trend\": \"string (Stable/Improving/Declining)\"
  },
  \"insights\": [\"string\"],
  \"risks\": [\"string\"],
  \"recommendations\": [\"string\"],
  \"confidence_level\": \"High|Medium|Low\",
  \"metadata\": {
    \"chart_data\": null | { \"type\": \"bar|line|pie\", \"labels\": [], \"datasets\": [] }
  }
}";
    }
}
