<?php

namespace App\Services\Knowledge;

use App\Models\AiAgent;
use App\Models\KnowledgeChunk;
use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class KnowledgeRetriever
{
    public function __construct(
        private readonly OpenAIEmbeddingService $embeddingService
    ) {
    }

    /**
     * Backward-compatible website retrieval.
     *
     * Website channels continue calling this method, but the actual retrieval
     * is now agent-scoped so the same AI agent can safely serve Website,
     * WhatsApp and future channels from one knowledge base.
     */
    public function retrieve(
        Website $website,
        string $question,
        ?int $limit = null
    ): array {
        $website->loadMissing('aiAgent');

        if ($website->aiAgent) {
            return $this->retrieveForAgent(
                agent: $website->aiAgent,
                question: $question,
                limit: $limit,
            );
        }

        /*
         * Temporary legacy fallback for an old website that has not yet been
         * provisioned with an ai_agent_id.
         */
        return $this->retrieveLegacyWebsite(
            website: $website,
            question: $question,
            limit: $limit,
        );
    }

    public function retrieveForAgent(
        AiAgent $agent,
        string $question,
        ?int $limit = null
    ): array {
        $question = trim($question);

        if ($question === '') {
            return [];
        }

        $limit ??= (int) config('knowledge.retrieval.limit', 8);

        $questionEmbedding = $this->embeddingService->embedOne(
            $question,
            (int) $agent->tenant_id,
        );

        /*
         * Agent-scoped knowledge is the primary source. Include website-linked
         * legacy chunks as a compatibility path because older website indexes
         * may predate ai_agent_id on knowledge_chunks. Website IDs are first
         * constrained to this tenant + agent, so this does not leak knowledge
         * across tenants or agents.
         */
        $websiteIds = Website::query()
            ->where('tenant_id', $agent->tenant_id)
            ->where('ai_agent_id', $agent->id)
            ->pluck('id');

        $chunks = KnowledgeChunk::query()
            ->whereNotNull('embedding')
            ->where('is_active', true)
            ->where(function ($query) use ($agent, $websiteIds): void {
                $query->where(function ($query) use ($agent): void {
                    $query->where('tenant_id', $agent->tenant_id)
                        ->where('ai_agent_id', $agent->id);
                });

                if ($websiteIds->isNotEmpty()) {
                    $query->orWhereIn('website_id', $websiteIds);
                }
            })
            ->with([
                'knowledgePage',
                'knowledgeSource',
            ])
            ->get();

        if ($chunks->isEmpty()) {
            Log::info('No active knowledge chunks were found for AI agent.', [
                'tenant_id' => $agent->tenant_id,
                'ai_agent_id' => $agent->id,
            ]);

            return [];
        }

        return $this->rankChunks(
            chunks: $chunks,
            questionEmbedding: $questionEmbedding,
            limit: $limit,
        );
    }

    private function retrieveLegacyWebsite(
        Website $website,
        string $question,
        ?int $limit = null
    ): array {
        $question = trim($question);

        if ($question === '') {
            return [];
        }

        $limit ??= (int) config('knowledge.retrieval.limit', 8);

        $questionEmbedding = $this->embeddingService->embedOne(
            $question,
            (int) $website->tenant_id,
        );

        $chunks = KnowledgeChunk::query()
            ->where('website_id', $website->id)
            ->whereNotNull('embedding')
            ->where('is_active', true)
            ->with([
                'knowledgePage',
                'knowledgeSource',
            ])
            ->get();

        return $this->rankChunks(
            chunks: $chunks,
            questionEmbedding: $questionEmbedding,
            limit: $limit,
        );
    }

    private function rankChunks(
        Collection $chunks,
        array $questionEmbedding,
        int $limit,
    ): array {
        if ($chunks->isEmpty()) {
            return [];
        }

        $scoredChunks = $chunks
            ->map(function (KnowledgeChunk $chunk) use ($questionEmbedding) {
                $chunkEmbedding = $chunk->embedding;

                if (!is_array($chunkEmbedding) || $chunkEmbedding === []) {
                    return null;
                }

                return [
                    'chunk' => $chunk,
                    'score' => $this->cosineSimilarity(
                        $questionEmbedding,
                        $chunkEmbedding
                    ),
                ];
            })
            ->filter()
            ->filter(function (array $result): bool {
                return $result['score'] >= (float) config(
                    'knowledge.retrieval.minimum_score',
                    0.20
                );
            })
            ->sortByDesc('score')
            ->values();

        return $this->limitPerSource($scoredChunks, $limit)
            ->map(function (array $result): array {
                return $this->formatResult(
                    $result['chunk'],
                    $result['score']
                );
            })
            ->values()
            ->all();
    }

    private function limitPerSource(
        Collection $results,
        int $totalLimit
    ): Collection {
        $maximumPerSource = (int) config(
            'knowledge.retrieval.max_chunks_per_source',
            3
        );

        $selected = collect();
        $sourceCounts = [];

        foreach ($results as $result) {
            /** @var KnowledgeChunk $chunk */
            $chunk = $result['chunk'];
            $sourceKey = $this->sourceKey($chunk);
            $sourceCounts[$sourceKey] ??= 0;

            if ($sourceCounts[$sourceKey] >= $maximumPerSource) {
                continue;
            }

            $selected->push($result);
            $sourceCounts[$sourceKey]++;

            if ($selected->count() >= $totalLimit) {
                break;
            }
        }

        return $selected;
    }

    private function sourceKey(KnowledgeChunk $chunk): string
    {
        if ($chunk->knowledge_source_id !== null) {
            return 'uploaded-source-' . $chunk->knowledge_source_id;
        }

        if ($chunk->knowledge_page_id !== null) {
            return 'knowledge-page-' . $chunk->knowledge_page_id;
        }

        return 'unknown-chunk-' . $chunk->id;
    }

    private function formatResult(
        KnowledgeChunk $chunk,
        float $score
    ): array {
        if ($chunk->knowledgeSource !== null) {
            return [
                'chunk_id' => $chunk->id,
                'source_id' => $chunk->knowledgeSource->id,
                'source_type' => $chunk->knowledgeSource->source_type,
                'source_name' => $chunk->knowledgeSource->original_name
                    ?? $chunk->knowledgeSource->name
                    ?? 'Uploaded knowledge source',
                'source_url' => null,
                'page_number' => $chunk->page_number,
                'section_title' => $chunk->section_title,
                'content' => $chunk->chunk_text,
                'score' => round($score, 6),
            ];
        }

        if ($chunk->knowledgePage !== null) {
            return [
                'chunk_id' => $chunk->id,
                'source_id' => $chunk->knowledgePage->id,
                'source_type' => $chunk->knowledgePage->source_type ?: 'manual',
                'source_name' => $chunk->knowledgePage->title
                    ?? $chunk->knowledgePage->url
                    ?? 'Knowledge page',
                'source_url' => $chunk->knowledgePage->url,
                'page_number' => null,
                'section_title' => null,
                'content' => $chunk->chunk_text,
                'score' => round($score, 6),
            ];
        }

        return [
            'chunk_id' => $chunk->id,
            'source_id' => null,
            'source_type' => 'unknown',
            'source_name' => 'Unknown source',
            'source_url' => null,
            'page_number' => $chunk->page_number,
            'section_title' => $chunk->section_title,
            'content' => $chunk->chunk_text,
            'score' => round($score, 6),
        ];
    }

    private function cosineSimilarity(
        array $firstVector,
        array $secondVector
    ): float {
        $vectorLength = min(count($firstVector), count($secondVector));

        if ($vectorLength === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $firstMagnitude = 0.0;
        $secondMagnitude = 0.0;

        for ($index = 0; $index < $vectorLength; $index++) {
            $firstValue = (float) $firstVector[$index];
            $secondValue = (float) $secondVector[$index];

            $dotProduct += $firstValue * $secondValue;
            $firstMagnitude += $firstValue * $firstValue;
            $secondMagnitude += $secondValue * $secondValue;
        }

        if ($firstMagnitude <= 0 || $secondMagnitude <= 0) {
            return 0.0;
        }

        return $dotProduct / (
            sqrt($firstMagnitude) * sqrt($secondMagnitude)
        );
    }
}
