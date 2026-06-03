<?php

namespace App\Services;

class TextChunkerService
{
    public function chunk(string $text, int $maxLength = 1200): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);

        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (strlen($current . ' ' . $sentence) > $maxLength) {
                $chunks[] = trim($current);
                $current = $sentence;
            } else {
                $current .= ' ' . $sentence;
            }
        }

        if (trim($current)) {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks));
    }
}