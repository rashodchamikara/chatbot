<?PHP
namespace App\Services\Knowledge\Extraction;

use RuntimeException;

class PlainTextExtractor implements DocumentExtractor
{
    /**
     * @return array<int, ExtractedSegment>
     */
    public function extract(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("Plain text file does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("Plain text file is not readable: {$filePath}");
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read plain text file: {$filePath}");
        }

        $contents = $this->normalizeText($contents);

        if (trim($contents) === '') {
            throw new RuntimeException('The uploaded text file does not contain readable text.');
        }

        return [
            new ExtractedSegment(
                text: $contents,
                pageNumber: null,
                sectionTitle: null,
                metadata: [
                    'extractor' => 'plain_text',
                    'filename' => basename($filePath),
                    'character_count' => mb_strlen($contents, 'UTF-8'),
                    'line_count' => substr_count($contents, "\n") + 1,
                ],
            ),
        ];
    }

    private function normalizeText(string $contents): string
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (!mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $contents = preg_replace('/[^\P{C}\t\n]+/u', '', $contents) ?? $contents;
        $contents = preg_replace("/\n{3,}/", "\n\n", $contents) ?? $contents;

        return trim($contents);
    }
}