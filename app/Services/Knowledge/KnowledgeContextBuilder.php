<?php

namespace App\Services\Knowledge;

class KnowledgeContextBuilder
{
    /**
     * Convert retrieved chunks into prompt context.
     */
    public function build(array $results): string
    {
        if ($results === []) {
            return 'No relevant knowledge sources were found.';
        }

        return collect($results)
            ->values()
            ->map(function (
                array $result,
                int $index
            ) {
                $reference = 'S' . ($index + 1);

                $sourceName =
                    $result['source_name']
                    ?? 'Unknown source';

                $sourceType =
                    $result['source_type']
                    ?? 'unknown';

                $location = $this->location(
                    $result
                );

                $content = trim(
                    $result['content'] ?? ''
                );

                return <<<TEXT
[{$reference}]
Source: {$sourceName}
Source type: {$sourceType}
Location: {$location}

{$content}
TEXT;
            })
            ->implode("\n\n---\n\n");
    }

    /**
     * Determine the most useful source location.
     */
    private function location(array $result): string
    {
        if (!empty($result['page_number'])) {
            return 'Page '
                . $result['page_number'];
        }

        if (!empty($result['section_title'])) {
            return $result['section_title'];
        }

        if (!empty($result['source_url'])) {
            return $result['source_url'];
        }

        return 'Document';
    }
}