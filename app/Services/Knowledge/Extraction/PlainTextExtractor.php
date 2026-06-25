<?PHP 
namespace App\Services\Knowledge\Extraction;

use RuntimeException;

class PlainTextExtractor implements DocumentExtractor
{
    /**
     * Extract readable text from a plain-text file.
     *
     * Supported examples:
     * - .txt
     * - .md
     * - .log
     * - text-based CSV when routed here
     *
     * @return array<int, ExtractedSegment>
     */
    public function extract(string $filePath): array
    {
        $this->validateFile($filePath);

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new RuntimeException(
                'Unable to read the uploaded plain-text file.'
            );
        }

        if ($contents === '') {
            throw new RuntimeException(
                'The uploaded plain-text file is empty.'
            );
        }

        $contents = $this->removeUtf8Bom($contents);
        $contents = $this->convertToUtf8($contents);
        $contents = $this->normalizeText($contents);

        if ($contents === '') {
            throw new RuntimeException(
                'No readable text was found in the uploaded file.'
            );
        }

        return [
            new ExtractedSegment(
                text: $contents,
                pageNumber: null,
                sectionTitle: null,
                metadata: [
                    'extractor' => 'plain_text',
                    'filename' => basename($filePath),
                    'character_count' => mb_strlen(
                        $contents,
                        'UTF-8'
                    ),
                    'line_count' => $this->countLines(
                        $contents
                    ),
                ],
            ),
        ];
    }

    /**
     * Confirm the temporary file exists and is readable.
     */
    private function validateFile(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(
                'The plain-text file does not exist: '
                . $filePath
            );
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException(
                'The plain-text file is not readable: '
                . $filePath
            );
        }

        $fileSize = filesize($filePath);

        if ($fileSize === false || $fileSize <= 0) {
            throw new RuntimeException(
                'The uploaded plain-text file is empty.'
            );
        }
    }

    /**
     * Remove the UTF-8 byte order mark when present.
     */
    private function removeUtf8Bom(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            return substr($contents, 3);
        }

        return $contents;
    }

    /**
     * Convert common encodings into UTF-8.
     */
    private function convertToUtf8(string $contents): string
    {
        if (
            mb_check_encoding(
                $contents,
                'UTF-8'
            )
        ) {
            return $contents;
        }

        $detectedEncoding = mb_detect_encoding(
            $contents,
            [
                'UTF-8',
                'Windows-1252',
                'ISO-8859-1',
                'ASCII',
            ],
            true
        );

        if ($detectedEncoding === false) {
            throw new RuntimeException(
                'The text file character encoding could not be detected.'
            );
        }

        $converted = mb_convert_encoding(
            $contents,
            'UTF-8',
            $detectedEncoding
        );

        if ($converted === '') {
            throw new RuntimeException(
                'The text file could not be converted to UTF-8.'
            );
        }

        return $converted;
    }

    /**
     * Normalize whitespace while retaining paragraphs and line structure.
     */
    private function normalizeText(string $contents): string
    {
        /*
         * Normalize Windows and older Mac line endings.
         */
        $contents = str_replace(
            ["\r\n", "\r"],
            "\n",
            $contents
        );

        /*
         * Remove null bytes and unsupported control characters.
         * Tabs and new lines are retained.
         */
        $contents = str_replace(
            "\0",
            '',
            $contents
        );

        $contents = preg_replace(
            '/[^\P{C}\t\n]/u',
            '',
            $contents
        ) ?? $contents;

        /*
         * Remove trailing spaces from each line.
         */
        $lines = explode(
            "\n",
            $contents
        );

        $lines = array_map(
            static fn (string $line): string =>
                rtrim($line),
            $lines
        );

        $contents = implode(
            "\n",
            $lines
        );

        /*
         * Limit excessive consecutive blank lines.
         */
        $contents = preg_replace(
            "/\n{4,}/",
            "\n\n\n",
            $contents
        ) ?? $contents;

        return trim($contents);
    }

    private function countLines(string $contents): int
    {
        if ($contents === '') {
            return 0;
        }

        return substr_count(
            $contents,
            "\n"
        ) + 1;
    }
}