<?php

namespace App\Services\Knowledge\Extraction;

use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfTextExtractor implements DocumentExtractor
{
    /**
     * @return array<int, ExtractedSegment>
     */
    public function extract(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("PDF file does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("PDF file is not readable: {$filePath}");
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);

        $pages = $pdf->getPages();
        $segments = [];

        foreach ($pages as $index => $page) {
            $text = trim($this->normalizeText($page->getText()));

            if ($text === '') {
                continue;
            }

            $segments[] = new ExtractedSegment(
                text: $text,
                pageNumber: $index + 1,
                sectionTitle: null,
                metadata: [
                    'extractor' => 'pdf_text',
                    'page_number' => $index + 1,
                    'filename' => basename($filePath),
                    'character_count' => mb_strlen($text, 'UTF-8'),
                ],
            );
        }

        if (empty($segments)) {
            throw new RuntimeException(
                'No readable text was extracted from this PDF. If this is a scanned image PDF, OCR support is required.'
            );
        }

        return $segments;
    }

    private function normalizeText(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}