<?php

namespace App\Jobs\Knowledge;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class FinalizeKnowledgeSourceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $sourceId
    ) {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $source = KnowledgeSource::query()
                ->lockForUpdate()
                ->findOrFail($this->sourceId);

            $newVersion =
                $source->processing_version;

            KnowledgeChunk::query()
                ->where(
                    'knowledge_source_id',
                    $source->id
                )
                ->update([
                    'is_active' => false,
                ]);

            KnowledgeChunk::query()
                ->where(
                    'knowledge_source_id',
                    $source->id
                )
                ->where(
                    'processing_version',
                    $newVersion
                )
                ->whereNotNull('embedding')
                ->update([
                    'is_active' => true,
                ]);

            $source->update([
                'status' => 'ready',
                'active_version' =>
                    $newVersion,
                'processed_at' => now(),
                'processing_error' => null,
            ]);
        });
    }
}