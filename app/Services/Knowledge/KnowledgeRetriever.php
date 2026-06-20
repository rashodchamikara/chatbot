<?php

namespace App\Services\Knowledge;

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

    public function retrieve(
        Website $website,
        string $question,
        ?int $limit = null
    ): array {
        $question = trim($question);

        if ($question === '') {
            return [];
        }

        $limit ??= (int) config(
            'knowledge.retrieval.limit',
            8
        );

        $questionEmbedding =
            $this->embeddingService->embedOne(
                $question,
                $website->tenant_id
            );
        $chunks = KnowledgeChunk::query()
            ->where(
                'website_id',
                $website->id
            )
            ->whereNotNull('embedding')
            ->where('is_active', true)
            ->with([
                'knowledgePage',
                'knowledgeSource',
            ])
            ->get();

        if ($chunks->isEmpty()) {
            Log::info(
                'No active knowledge chunks were found.',
                [
                    'website_id' => $website->id,
                ]
            );

            return [];
        }

        $scoredChunks = $chunks
            ->map(function (
                KnowledgeChunk $chunk
            ) use ($questionEmbedding) {
                $chunkEmbedding = $chunk->embedding;

                if (
                    !is_array($chunkEmbedding)
                    || $chunkEmbedding === []
                ) {
                    return null;
                }

                $score = $this->cosineSimilarity(
                    $questionEmbedding,
                    $chunkEmbedding
                );

                return [
                    'chunk' => $chunk,
                    'score' => $score,
                ];
            })
            ->filter()
            ->filter(function (array $result) {
                return $result['score']
                    >= (float) config(
                        'knowledge.retrieval.minimum_score',
                        0.20
                    );
            })
            ->sortByDesc('score')
            ->values();

       
        $selectedChunks = $this->limitPerSource(
            $scoredChunks,
            $limit
        );

        return $selectedChunks
            ->map(function (array $result) {
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

            if (
                $sourceCounts[$sourceKey]
                >= $maximumPerSource
            ) {
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

    private function sourceKey(
        KnowledgeChunk $chunk
    ): string {
        if ($chunk->knowledge_source_id !== null) {
            return 'uploaded-source-'
                . $chunk->knowledge_source_id;
        }

        if ($chunk->knowledge_page_id !== null) {
            return 'knowledge-page-'
                . $chunk->knowledge_page_id;
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

                'source_id' =>
                    $chunk->knowledgeSource->id,

                'source_type' =>
                    $chunk->knowledgeSource->source_type,

                'source_name' =>
                    $chunk->knowledgeSource->name,

                'source_url' => null,

                'page_number' =>
                    $chunk->page_number,

                'section_title' =>
                    $chunk->section_title,

                'content' =>
                    $chunk->chunk_text,

                'score' =>
                    round($score, 6),
            ];
        }

       
        if ($chunk->knowledgePage !== null) {
            return [
                'chunk_id' => $chunk->id,

                'source_id' =>
                    $chunk->knowledgePage->id,

                'source_type' => 'url',

                'source_name' =>
                    $chunk->knowledgePage->title
                    ?? $chunk->knowledgePage->url
                    ?? 'Website page',

                'source_url' =>
                    $chunk->knowledgePage->url
                    ?? null,

                'page_number' => null,

                'section_title' => null,

                'content' =>
                    $chunk->chunk_text,

                'score' =>
                    round($score, 6),
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
        $vectorLength = min(
            count($firstVector),
            count($secondVector)
        );

        if ($vectorLength === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $firstMagnitude = 0.0;
        $secondMagnitude = 0.0;

        for (
            $index = 0;
            $index < $vectorLength;
            $index++
        ) {
            $firstValue = (float) $firstVector[$index];
            $secondValue = (float) $secondVector[$index];

            $dotProduct +=
                $firstValue * $secondValue;

            $firstMagnitude +=
                $firstValue * $firstValue;

            $secondMagnitude +=
                $secondValue * $secondValue;
        }

        if (
            $firstMagnitude <= 0
            || $secondMagnitude <= 0
        ) {
            return 0.0;
        }

        return $dotProduct / (
            sqrt($firstMagnitude)
            * sqrt($secondMagnitude)
        );
    }
}