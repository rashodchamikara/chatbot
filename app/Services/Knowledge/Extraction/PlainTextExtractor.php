<?PHP
namespace App\Services\Knowledge\Extraction;

use App\Models\KnowledgeSource;
use RuntimeException;

class PlainTextExtractor implements DocumentExtractor
{
    /**
     * @return array<int, ExtractedSegment>
     */
    public function extract(KnowledgeSource $source, string $localPath): array
    {
        if (!is_file($localPath)) {
            throw new RuntimeException("Plain text file does not exist: {$localPath}");
        }

        if (!is_readable($localPath)) {
            throw new RuntimeException("Plain text file is not readable: {$localPath}");
        }

        $contents = file_get_contents($localPath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read plain text file: {$localPath}");
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
                    'source_id' => $source->id,
                    'original_name' => $source->original_name,
                    'mime_type' => $source->mime_type,
                    'extension' => $source->extension,
                    'character_count' => mb_strlen($contents, 'UTF-8'),
                    'line_count' => $this->countLines($contents),
                ],
            ),
        ];
    }

    private function normalizeText(string $contents): string
    {
        // Remove UTF-8 BOM if present.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (!mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        // Normalize line endings.
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);

        // Remove unsafe control characters but keep tabs/newlines.
        $contents = preg_replace('/[^\P{C}\t\n]+/u', '', $contents) ?? $contents;

        // Reduce excessive blank lines.
        $contents = preg_replace("/\n{3,}/", "\n\n", $contents) ?? $contents;

        return trim($contents);
    }

    private function countLines(string $contents): int
    {
        if ($contents === '') {
            return 0;
        }

        return substr_count($contents, "\n") + 1;
    }
}