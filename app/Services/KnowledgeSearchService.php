<?php

namespace App\Services;

use App\Models\Website;
use App\Models\KnowledgeChunk;

class KnowledgeSearchService
{
    public function __construct(
        protected EmbeddingService $embeddingService
    ) {}

    public function search(Website $website, string $query, int $limit = 5): array
    {
        $queryEmbedding = $this->embeddingService->embed($query);

        $chunks = KnowledgeChunk::query()
        ->where(
            'website_id',
            $website->id
        )
        ->whereNotNull('embedding')
        ->where(function ($query) {
            /*
            * Existing crawler rows may predate
            * the is_active column value.
            */
            $query
                ->where('is_active', true)
                ->orWhereNull('is_active');
        })
        ->with([
            'knowledgePage',
            'knowledgeSource',
        ])
        ->get();

        $ranked = [];

        foreach ($chunks as $chunk) {
            $score = $this->embeddingService->cosineSimilarity(
                $queryEmbedding,
                $chunk->embedding
            );

            $ranked[] = [
                'score' => $score,
                'text' => $chunk->chunk_text,
                'url' => $chunk->page->url,
                'title' => $chunk->page->title,
            ];
        }

        usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($ranked, 0, $limit);
    }
}