<?php

namespace App\Jobs\Knowledge;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeSource;
use App\Services\Knowledge\TokenChunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ChunkKnowledgeSourceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public readonly int $sourceId
    ) {
    }

    public function handle(
        TokenChunker $chunker
    ): void {
        $source = KnowledgeSource::findOrFail(
            $this->sourceId
        );

        $compressed = Storage::disk(
            $source->storage_disk
        )->get(
            $source->extracted_path
        );

        $json = gzdecode($compressed);

        if ($json === false) {
            throw new RuntimeException(
                'Unable to decode extracted document.'
            );
        }

        $segments = json_decode(
            $json,
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $chunkRows = [];
        $chunkIndex = 0;

        foreach ($segments as $segment) {
            $chunks = $chunker->chunk(
                $segment['text']
            );

            foreach ($chunks as $chunk) {
                $chunkRows[] = [
                    /*
                     * Uploaded documents do not use
                     * knowledge_page_id.
                     */
                    'knowledge_page_id' => null,

                    'knowledge_source_id' =>
                        $source->id,

                    'website_id' =>
                        $source->website_id,

                    /*
                     * Continue using the existing
                     * chunk_text column.
                     */
                    'chunk_text' =>
                        $chunk['content'],

                    /*
                     * Embedding is created later.
                     */
                    'embedding' => null,

                    /*
                     * Continue using the existing
                     * chunk_index column.
                     */
                    'chunk_index' =>
                        $chunkIndex++,

                    'processing_version' =>
                        $source->processing_version,

                    'token_count' =>
                        $chunk['token_count'],

                    'page_number' =>
                        $segment['page_number']
                        ?? null,

                    'section_title' =>
                        $segment['section_title']
                        ?? null,

                    'content_hash' => hash(
                        'sha256',
                        $chunk['content']
                    ),

                    'metadata' => json_encode(
                        $segment['metadata'] ?? [],
                        JSON_THROW_ON_ERROR
                    ),

                    /*
                     * Do not expose partially processed
                     * new versions to retrieval.
                     */
                    'is_active' => false,

                    'embedded_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($chunkRows === []) {
            throw new RuntimeException(
                'No chunks were generated.'
            );
        }

        DB::transaction(
            function () use (
                $source,
                $chunkRows
            ) {
                /*
                 * Safe for retries.
                 */
                KnowledgeChunk::query()
                    ->where(
                        'knowledge_source_id',
                        $source->id
                    )
                    ->where(
                        'processing_version',
                        $source->processing_version
                    )
                    ->delete();

                foreach (
                    array_chunk($chunkRows, 500)
                    as $batch
                ) {
                    KnowledgeChunk::insert($batch);
                }

                $source->update([
                    'status' => 'embedding',
                    'chunk_count' =>
                        count($chunkRows),
                ]);
            }
        );

        EmbedKnowledgeSourceChunksJob::dispatch(
            $source->id
        )->onQueue('knowledge-embed');
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