<?php

namespace App\Jobs;

use App\Models\Website;
use App\Models\KnowledgeChunk;
use App\Services\WebsiteCrawlerService;
use App\Services\TextChunkerService;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IndexWebsiteKnowledgeJob implements ShouldQueue
{
    use Queueable;
    public int $timeout = 1800;
    public int $tries = 2;

    public function __construct(
        public int $websiteId,
        public ?int $limit = NULL
    ) {}

    public function handle(
        WebsiteCrawlerService $crawler,
        TextChunkerService $chunker,
        EmbeddingService $embeddingService
    ): void {
        $website = Website::findOrFail($this->websiteId);

        $website->update([
            'indexing_status' => 'processing',
            'indexing_started_at' => now(),
            'indexing_completed_at' => null,
            'indexing_error' => null,
        ]);

        try {
            Log::info('Website indexing started', [
                'website_id' => $website->id,
                'domain' => $website->domain,
                'limit' => $this->limit ?? 'all',
            ]);

            $crawler->crawlWebsite($website, $this->limit);

            $pages = $website->knowledgePages()
                ->where('is_indexed', false)
                ->where('is_active', true)
                ->get();

            foreach ($pages as $page) {
                if (!$page->content || strlen($page->content) < 50) {
                    continue;
                }

                $page->chunks()->delete();

                $chunks = $chunker->chunk($page->content);

                foreach ($chunks as $index => $chunkText) {
                    $embedding = $embeddingService->embed($chunkText);

                    KnowledgeChunk::create([
                        'knowledge_page_id' => $page->id,
                        'website_id' => $website->id,
                        'chunk_text' => $chunkText,
                        'embedding' => $embedding,
                        'chunk_index' => $index,
                    ]);
                }

                $page->update([
                    'is_indexed' => true,
                    'indexed_at' => now(),
                    'content_hash' => hash('sha256', $page->content),
                ]);
            }

            $website->update([
                'indexing_status' => 'completed',
                'indexing_completed_at' => now(),
                'indexing_error' => null,
            ]);

            Log::info('Website indexing completed', [
                'website_id' => $website->id,
                'pages_indexed' => $pages->count(),
            ]);

        } catch (\Throwable $e) {
            $website->update([
                'indexing_status' => 'failed',
                'indexing_error' => $e->getMessage(),
                'indexing_completed_at' => now(),
            ]);

            Log::error('Website indexing failed', [
                'website_id' => $website->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $website = Website::find($this->websiteId);

        if ($website) {
            $website->update([
                'indexing_status' => 'failed',
                'indexing_error' => $exception->getMessage(),
                'indexing_completed_at' => now(),
            ]);
        }
    }

}
