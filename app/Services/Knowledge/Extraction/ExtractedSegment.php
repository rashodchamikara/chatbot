<?php

namespace App\Services\Knowledge\Extraction;

final readonly class ExtractedSegment
{
    public function __construct(
        public string $text,
        public ?int $pageNumber = null,
        public ?string $sectionTitle = null,
        public array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'page_number' => $this->pageNumber,
            'section_title' => $this->sectionTitle,
            'metadata' => $this->metadata,
        ];
    }
}