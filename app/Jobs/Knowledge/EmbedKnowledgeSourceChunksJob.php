<?php

namespace App\Jobs\Knowledge;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeSource;
use App\Services\Knowledge\OpenAIEmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class EmbedKnowledgeSourceChunksJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 180;

    public function __construct(
        public readonly int $sourceId
    ) {
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                'knowledge-source-embedding-'
                . $this->sourceId
            ))->expireAfter(240),
        ];
    }

    public function backoff(): array
    {
        return [5, 15, 60, 180];
    }

    public function handle(
        OpenAIEmbeddingService $embeddingService
    ): void {
        $source = KnowledgeSource::findOrFail(
            $this->sourceId
        );

        $chunks = KnowledgeChunk::query()
            ->where(
                'knowledge_source_id',
                $source->id
            )
            ->where(
                'processing_version',
                $source->processing_version
            )
            ->whereNull('embedded_at')
            ->orderBy('id')
            ->limit(
                config(
                    'knowledge.embedding.batch_size'
                )
            )
            ->get();

        if ($chunks->isEmpty()) {
            FinalizeKnowledgeSourceJob::dispatch(
                $source->id
            )->onQueue('knowledge-embed');

            return;
        }

        $embeddings =
            $embeddingService->embedMany(
                $chunks
                    ->pluck('chunk_text')
                    ->all(),
                $source->tenant_id
            );

        foreach ($chunks as $index => $chunk) {
            $chunk->update([
                /*
                 * Uses the existing JSON column.
                 */
                'embedding' =>
                    $embeddings[$index],

                'embedded_at' => now(),
            ]);
        }

        $source->increment(
            'embedding_tokens',
            $chunks->sum('token_count')
        );

        self::dispatch($source->id)
            ->delay(now()->addSecond())
            ->onQueue('knowledge-embed');
    }

    public function failed(
        Throwable $exception
    ): void {
        KnowledgeSource::whereKey(
            $this->sourceId
        )->update([
            'status' => 'failed',
            'processing_error' =>
                mb_substr(
                    $exception->getMessage(),
                    0,
                    5000
                ),
        ]);
    }
}