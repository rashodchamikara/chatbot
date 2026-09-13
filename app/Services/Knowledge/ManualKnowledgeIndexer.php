<?php

namespace App\Services\Knowledge;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgePage;
use App\Services\TextChunkerService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManualKnowledgeIndexer
{
    public function __construct(
        private readonly TextChunkerService $chunker,
        private readonly OpenAIEmbeddingService $embeddingService,
    ) {
    }

    public function index(KnowledgePage $page): int
    {
        $content = trim((string) $page->content);

        if (mb_strlen($content) < 50) {
            throw new RuntimeException(
                'Knowledge content must contain at least 50 characters before it can be indexed.'
            );
        }

        if (!$page->tenant_id) {
            throw new RuntimeException('Knowledge page has no tenant_id.');
        }

        if (!$page->ai_agent_id) {
            throw new RuntimeException('Knowledge page has no ai_agent_id.');
        }

        $chunks = $this->chunker->chunk($content);

        if ($chunks === []) {
            throw new RuntimeException('No knowledge chunks were generated.');
        }

        $embeddings = $this->embeddingService->embedMany(
            $chunks,
            (int) $page->tenant_id,
        );

        DB::transaction(function () use ($page, $chunks, $embeddings): void {
            $page->chunks()->delete();

            foreach ($chunks as $index => $chunkText) {
                KnowledgeChunk::query()->create([
                    'tenant_id' => $page->tenant_id,
                    'ai_agent_id' => $page->ai_agent_id,
                    'knowledge_page_id' => $page->id,
                    'knowledge_source_id' => null,
                    'website_id' => $page->website_id,
                    'chunk_text' => $chunkText,
                    'embedding' => $embeddings[$index],
                    'chunk_index' => $index,
                    'processing_version' => 1,
                    'content_hash' => hash('sha256', $chunkText),
                    'metadata' => [
                        'source_type' => $page->source_type,
                    ],
                    'is_active' => (bool) $page->is_active,
                    'embedded_at' => now(),
                ]);
            }

            $page->forceFill([
                'is_indexed' => true,
                'indexed_at' => now(),
                'content_hash' => hash('sha256', (string) $page->content),
            ])->save();
        });

        return count($chunks);
    }
}
