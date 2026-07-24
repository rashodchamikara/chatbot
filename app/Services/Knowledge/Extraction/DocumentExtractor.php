<?php

namespace App\Services\Knowledge\Extraction;

interface DocumentExtractor
{
    /**
     * @return array<int, ExtractedSegment>
     */
    public function extract(string $filePath): array;
}