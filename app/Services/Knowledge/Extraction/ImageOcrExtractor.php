<?php

namespace App\Services\Knowledge\Extraction;

use RuntimeException;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ImageOcrExtractor implements DocumentExtractor
{
    /**
     * @return array<int, ExtractedSegment>
     */
    public function extract(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("Image file does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("Image file is not readable: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'bmp', 'tif', 'tiff'], true)) {
            throw new RuntimeException(
                "Unsupported image OCR format [{$extension}]. Supported formats: jpg, jpeg, png, bmp, tif, tiff."
            );
        }

        $text = (new TesseractOCR($filePath))
            ->lang('eng')
            ->run();

        $text = $this->normalizeText($text);

        if ($text === '') {
            throw new RuntimeException(
                'No readable text was extracted from this image. The image may be too blurry, handwritten, or not text-based.'
            );
        }

        return [
            new ExtractedSegment(
                text: $text,
                pageNumber: null,
                sectionTitle: null,
                metadata: [
                    'extractor' => 'image_ocr_tesseract',
                    'filename' => basename($filePath),
                    'character_count' => mb_strlen($text, 'UTF-8'),
                ],
            ),
        ];
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