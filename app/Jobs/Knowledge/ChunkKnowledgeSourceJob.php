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

        $disk = $source->storage_disk;
        $path = $source->extracted_storage_path;

        if (empty($disk)) {
            throw new RuntimeException("Knowledge source {$source->id} has no storage_disk.");
        }

        if (empty($path)) {
            throw new RuntimeException("Knowledge source {$source->id} has no extracted_storage_path.");
        }

        if (!Storage::disk($disk)->exists($path)) {
            throw new RuntimeException("Extracted file does not exist on disk [{$disk}]: {$path}");
        }

        $rawContents = Storage::disk($disk)->get($path);

        if ($rawContents === '') {
            throw new RuntimeException("Extracted file is empty: {$path}");
        }

        $json = str_ends_with($path, '.gz')
            ? gzdecode($rawContents)
            : $rawContents;

        if ($json === false) {
            throw new RuntimeException("Unable to decompress extracted file: {$path}");
        }

        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $segments = $payload['segments'] ?? [];

        if (empty($segments)) {
            throw new RuntimeException("Extracted file contains no segments: {$path}");
        }

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