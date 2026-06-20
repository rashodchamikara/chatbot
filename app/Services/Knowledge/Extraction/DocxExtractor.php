<?php

namespace App\Services\Knowledge\Extraction;

use App\Models\KnowledgeSource;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

class DocxExtractor implements DocumentExtractor
{
    public function supports(
        KnowledgeSource $source
    ): bool {
        return $source->extension === 'docx';
    }

    public function extract(
        KnowledgeSource $source,
        string $localPath
    ): array {
        $document = IOFactory::load($localPath);
        $segments = [];

        foreach (
            $document->getSections()
            as $sectionIndex => $section
        ) {
            $parts = [];

            foreach (
                $section->getElements()
                as $element
            ) {
                $text = $this->extractElementText(
                    $element
                );

                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            $sectionText = trim(
                implode("\n\n", $parts)
            );

            if ($sectionText === '') {
                continue;
            }

            $segments[] = new ExtractedSegment(
                text: $sectionText,
                sectionTitle:
                    'Section ' .
                    ($sectionIndex + 1),
                metadata: [
                    'extractor' =>
                        self::class,
                ],
            );
        }

        return $segments;
    }

    private function extractElementText(
        AbstractElement $element
    ): string {
        if ($element instanceof Text) {
            return trim(
                (string) $element->getText()
            );
        }

        if ($element instanceof TextRun) {
            $parts = [];

            foreach (
                $element->getElements()
                as $child
            ) {
                if (
                    $child instanceof
                    AbstractElement
                ) {
                    $parts[] =
                        $this->extractElementText(
                            $child
                        );
                }
            }

            return trim(
                implode(
                    ' ',
                    array_filter($parts)
                )
            );
        }

        if ($element instanceof Table) {
            $rows = [];

            foreach (
                $element->getRows()
                as $row
            ) {
                $cells = [];

                foreach (
                    $row->getCells()
                    as $cell
                ) {
                    $cellParts = [];

                    foreach (
                        $cell->getElements()
                        as $child
                    ) {
                        if (
                            $child instanceof
                            AbstractElement
                        ) {
                            $cellParts[] =
                                $this->extractElementText(
                                    $child
                                );
                        }
                    }

                    $cells[] = trim(
                        implode(
                            ' ',
                            array_filter(
                                $cellParts
                            )
                        )
                    );
                }

                $rows[] = implode(
                    ' | ',
                    $cells
                );
            }

            return trim(
                implode("\n", $rows)
            );
        }

        if (method_exists($element, 'getText')) {
            return trim(
                (string) $element->getText()
            );
        }

        return '';
    }
}