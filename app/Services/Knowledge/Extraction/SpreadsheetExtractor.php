<?php
namespace App\Services\Knowledge\Extraction;

use App\Models\KnowledgeSource;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadsheetExtractor implements DocumentExtractor
{
    public function supports(
        KnowledgeSource $source
    ): bool {
        return in_array(
            $source->extension,
            ['csv', 'xlsx'],
            true
        );
    }

    public function extract(
        KnowledgeSource $source,
        string $localPath
    ): array {
        $spreadsheet = IOFactory::load(
            $localPath
        );

        $segments = [];

        foreach (
            $spreadsheet->getWorksheetIterator()
            as $sheet
        ) {
            $rows = $sheet->toArray(
                null,
                true,
                true,
                false
            );

            if ($rows === []) {
                continue;
            }

            $headers = [];

            foreach ($rows[0] as $index => $header) {
                $headers[$index] =
                    trim((string) $header)
                    ?: 'Column ' . ($index + 1);
            }

            foreach (
                array_slice($rows, 1)
                as $rowIndex => $row
            ) {
                $pairs = [];

                foreach ($row as $index => $value) {
                    $value = trim(
                        (string) $value
                    );

                    if ($value === '') {
                        continue;
                    }

                    $pairs[] =
                        $headers[$index]
                        . ': '
                        . $value;
                }

                if ($pairs === []) {
                    continue;
                }

                $segments[] =
                    new ExtractedSegment(
                        text: implode(
                            ' | ',
                            $pairs
                        ),
                        sectionTitle:
                            $sheet->getTitle(),
                        metadata: [
                            'sheet' =>
                                $sheet->getTitle(),
                            'row' =>
                                $rowIndex + 2,
                            'extractor' =>
                                self::class,
                        ],
                    );
            }
        }

        return $segments;
    }
}