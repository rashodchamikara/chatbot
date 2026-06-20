<?php

namespace App\Services\Knowledge\Extraction;

use App\Models\KnowledgeSource;
use Smalot\PdfParser\Parser;

class PdfTextExtractor implements DocumentExtractor
{
    public function __construct(
        private readonly Parser $parser
    ) {
    }

    public function supports(
        KnowledgeSource $source
    ): bool {
        return $source->extension === 'pdf';
    }

    public function extract(
        KnowledgeSource $source,
        string $localPath
    ): array {
        $pdf = $this->parser->parseFile(
            $localPath
        );

        $segments = [];
        $totalCharacters = 0;
        $pages = $pdf->getPages();

        foreach ($pages as $index => $page) {
            $text = $this->normalize(
                $page->getText()
            );

            if ($text === '') {
                continue;
            }

            $totalCharacters += mb_strlen($text);

            $segments[] = new ExtractedSegment(
                text: $text,
                pageNumber: $index + 1,
                metadata: [
                    'extractor' =>
                        self::class,
                ],
            );
        }

        if (
            count($pages) > 0
            && $totalCharacters <
                count($pages) * 80
        ) {
            throw new OcrRequiredException(
                'The PDF appears to be scanned.'
            );
        }

        return $segments;
    }

    private function normalize(
        string $text
    ): string {
        $text = str_replace("\0", '', $text);

        $text = preg_replace(
            '/[ \t]+/u',
            ' ',
            $text
        );

        $text = preg_replace(
            '/\R{3,}/u',
            "\n\n",
            $text
        );

        return trim($text);
    }
}