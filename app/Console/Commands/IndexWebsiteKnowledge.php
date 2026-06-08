<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Website;
use App\Models\KnowledgeChunk;
use App\Services\WebsiteCrawlerService;
use App\Services\TextChunkerService;
use App\Services\EmbeddingService;

class IndexWebsiteKnowledge extends Command
{
    protected $signature = 'knowledge:index {website_id} {--limit=20}';

    protected $description = 'Crawl and index website content for AI knowledge retrieval';

    public function handle(
        WebsiteCrawlerService $crawler,
        TextChunkerService $chunker,
        EmbeddingService $embeddingService
    ) {
        $website = Website::findOrFail($this->argument('website_id'));

        $this->info('Crawling website...');
        $crawler->crawlWebsite($website, (int) $this->option('limit'));

        foreach ($website->knowledgePages()->where('is_indexed', false)->get() as $page) {
            $this->info('Indexing: ' . $page->url);

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
            ]);
        }

        $this->info('Knowledge indexing complete.');
    }
}