<?php

namespace App\Services\Knowledge\Extraction;

use App\Models\KnowledgeSource;

interface DocumentExtractor
{
    public function supports(
        KnowledgeSource $source
    ): bool;

    /**
     * @return array<ExtractedSegment>
     */
    public function extract(
        KnowledgeSource $source,
        string $localPath
    ): array;
}