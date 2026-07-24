<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use App\Models\Website;
use Illuminate\Support\Facades\Log;
use Throwable;

class KnowledgeSearchService
{
    public function __construct(
        protected EmbeddingService $embeddingService
    ) {
    }

    public function search(Website $website, string $query, int $limit = 5): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        try {
            $queryEmbedding = $this->embeddingService->embed($query);
        } catch (Throwable $exception) {
            Log::error('Knowledge search query embedding failed.', [
                'website_id' => $website->id,
                'query_preview' => mb_substr($query, 0, 200),
                'exception_class' => get_class($exception),
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return [];
        }

        if (!is_array($queryEmbedding) || empty($queryEmbedding)) {
            Log::warning('Knowledge search skipped because query embedding is invalid.', [
                'website_id' => $website->id,
                'query_preview' => mb_substr($query, 0, 200),
            ]);

            return [];
        }

        $chunks = KnowledgeChunk::query()
            ->where('website_id', $website->id)
            ->whereNotNull('embedding')
            ->where(function ($query) {
                /*
                 * Support both old crawler chunks and new uploaded-file chunks.
                 */
                $query->whereNotNull('knowledge_page_id')
                    ->orWhereNotNull('knowledge_source_id');
            })
            ->where(function ($query) {
                /*
                 * Existing crawler rows may predate the is_active column value.
                 */
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->with([
                'knowledgePage',
                'knowledgeSource',
                'page',
                'source',
            ])
            ->limit(500)
            ->get();

        $ranked = [];

        foreach ($chunks as $chunk) {
            $chunkEmbedding = $chunk->embedding;

            if (is_string($chunkEmbedding)) {
                $chunkEmbedding = json_decode($chunkEmbedding, true);
            }

            if (!is_array($chunkEmbedding) || empty($chunkEmbedding)) {
                continue;
            }

            if (count($chunkEmbedding) !== count($queryEmbedding)) {
                Log::warning('Knowledge chunk skipped because embedding dimensions do not match.', [
                    'website_id' => $website->id,
                    'chunk_id' => $chunk->id,
                    'query_dimensions' => count($queryEmbedding),
                    'chunk_dimensions' => count($chunkEmbedding),
                ]);

                continue;
            }

            $score = $this->embeddingService->cosineSimilarity(
                $queryEmbedding,
                $chunkEmbedding
            );

            $page = $chunk->knowledgePage ?? $chunk->page;
            $source = $chunk->knowledgeSource ?? $chunk->source;

            $url = $page?->url;

            $sourceName = $source?->original_name
                ?? $source?->name
                ?? $page?->title
                ?? $page?->url
                ?? 'Knowledge source';

            $title = $page?->title
                ?? $source?->original_name
                ?? $source?->name
                ?? 'Uploaded document';

            $sourceType = $chunk->knowledge_source_id !== null
                ? 'uploaded_file'
                : 'web_page';

            $ranked[] = [
                'score' => $score,
                'text' => $chunk->chunk_text,
                'url' => $url,
                'source' => $sourceName,
                'source_type' => $sourceType,
                'title' => $title,
                'chunk_id' => $chunk->id,
            ];
        }

        usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($ranked, 0, $limit);
    }
}