<?php

namespace App\Services\Knowledge\Extraction;

use App\Models\KnowledgeSource;

interface DocumentExtractor
{
    /**
     * @return array<int, ExtractedSegment>
     */
    public function extract(KnowledgeSource $source, string $localPath): array;
}