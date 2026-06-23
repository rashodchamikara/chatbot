<?php

namespace App\Services\Knowledge\Extraction;

use InvalidArgumentException;
use RuntimeException;

class ExtractorManager
{
    public function __construct(
        private readonly PdfTextExtractor $pdfExtractor,
        private readonly PlainTextExtractor $plainTextExtractor,
        private readonly DocxExtractor $docxExtractor,
        private readonly SpreadsheetExtractor $spreadsheetExtractor
    ) {
    }

    /**
     * Extract readable segments from a document.
     *
     * @return array<int, ExtractedSegment>
     */
    public function extract(
        string $filePath,
        ?string $mimeType = null,
        ?string $extension = null
    ): array {
        if (!is_file($filePath)) {
            throw new RuntimeException(
                'The file supplied for extraction does not exist: '
                . $filePath
            );
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException(
                'The file supplied for extraction is not readable: '
                . $filePath
            );
        }

        $extension = $this->normalizeExtension(
            $extension ?: pathinfo(
                $filePath,
                PATHINFO_EXTENSION
            )
        );

        $mimeType = $this->normalizeMimeType(
            $mimeType
                ?: $this->detectMimeType($filePath)
        );

        $extractor = $this->resolveExtractor(
            $extension,
            $mimeType
        );

        $segments = $extractor->extract(
            $filePath
        );

        return $this->normalizeSegments(
            $segments
        );
    }

    /**
     * Resolve the correct extractor for the uploaded file.
     */
    private function resolveExtractor(
        string $extension,
        string $mimeType
    ): DocumentExtractor {
        if (
            $extension === 'pdf'
            || $mimeType === 'application/pdf'
        ) {
            return $this->pdfExtractor;
        }

        if (
            in_array(
                $extension,
                ['docx'],
                true
            )
            || in_array(
                $mimeType,
                [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/msword',
                ],
                true
            )
        ) {
            return $this->docxExtractor;
        }

        if (
            in_array(
                $extension,
                ['csv', 'xlsx', 'xls'],
                true
            )
            || in_array(
                $mimeType,
                [
                    'text/csv',
                    'application/csv',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                true
            )
        ) {
            return $this->spreadsheetExtractor;
        }

        if (
            in_array(
                $extension,
                ['txt', 'md', 'log'],
                true
            )
            || str_starts_with(
                $mimeType,
                'text/'
            )
        ) {
            return $this->plainTextExtractor;
        }

        if (
            in_array(
                $extension,
                ['jpg', 'jpeg', 'png', 'webp'],
                true
            )
            || str_starts_with(
                $mimeType,
                'image/'
            )
        ) {
            throw new OcrRequiredException(
                'This image requires OCR processing before text can be extracted.'
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                'No document extractor is available for extension "%s" and MIME type "%s".',
                $extension !== '' ? $extension : 'unknown',
                $mimeType !== '' ? $mimeType : 'unknown'
            )
        );
    }

    /**
     * Ensure every extractor result is returned as ExtractedSegment.
     *
     * @param iterable<mixed> $segments
     * @return array<int, ExtractedSegment>
     */
    private function normalizeSegments(
        iterable $segments
    ): array {
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment instanceof ExtractedSegment) {
                $text = trim($segment->text);

                if ($text === '') {
                    continue;
                }

                $normalized[] =
                    new ExtractedSegment(
                        text: $text,
                        pageNumber:
                            $segment->pageNumber,
                        sectionTitle:
                            $segment->sectionTitle,
                        metadata:
                            $segment->metadata
                    );

                continue;
            }

            if (is_string($segment)) {
                $text = trim($segment);

                if ($text !== '') {
                    $normalized[] =
                        new ExtractedSegment(
                            text: $text
                        );
                }

                continue;
            }

            if (is_array($segment)) {
                $text = trim(
                    (string) (
                        $segment['text']
                        ?? ''
                    )
                );

                if ($text === '') {
                    continue;
                }

                $normalized[] =
                    new ExtractedSegment(
                        text: $text,

                        pageNumber:
                            isset(
                                $segment['page_number']
                            )
                                ? (int) $segment[
                                    'page_number'
                                ]
                                : null,

                        sectionTitle:
                            isset(
                                $segment[
                                    'section_title'
                                ]
                            )
                                ? trim(
                                    (string) $segment[
                                        'section_title'
                                    ]
                                )
                                : null,

                        metadata:
                            isset(
                                $segment['metadata']
                            )
                            && is_array(
                                $segment['metadata']
                            )
                                ? $segment[
                                    'metadata'
                                ]
                                : []
                    );
            }
        }

        return array_values(
            $normalized
        );
    }

    private function normalizeExtension(
        ?string $extension
    ): string {
        return strtolower(
            ltrim(
                trim(
                    (string) $extension
                ),
                '.'
            )
        );
    }

    private function normalizeMimeType(
        ?string $mimeType
    ): string {
        $mimeType = strtolower(
            trim(
                (string) $mimeType
            )
        );

        /*
         * MIME types can sometimes include charset information:
         *
         * text/plain; charset=utf-8
         */
        if (str_contains($mimeType, ';')) {
            $mimeType = trim(
                explode(
                    ';',
                    $mimeType,
                    2
                )[0]
            );
        }

        return $mimeType;
    }

    private function detectMimeType(
        string $filePath
    ): string {
        if (
            !function_exists(
                'finfo_open'
            )
        ) {
            return '';
        }

        $fileInfo = finfo_open(
            FILEINFO_MIME_TYPE
        );

        if ($fileInfo === false) {
            return '';
        }

        try {
            $mimeType = finfo_file(
                $fileInfo,
                $filePath
            );

            return is_string($mimeType)
                ? strtolower(
                    trim($mimeType)
                )
                : '';
        } finally {
            finfo_close(
                $fileInfo
            );
        }
    }
}