<?php

namespace App\Services\Knowledge;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIEmbeddingService
{
    /**
     * Generate an embedding for a single text string.
     */
    public function embedOne(
        string $text,
        ?int $tenantId = null
    ): array {
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException(
                'Cannot generate an embedding for empty text.'
            );
        }

        $embeddings = $this->embedMany(
            [$text],
            $tenantId
        );

        if (!isset($embeddings[0])) {
            throw new RuntimeException(
                'OpenAI did not return an embedding.'
            );
        }

        return $embeddings[0];
    }

    /**
     * Generate embeddings for multiple text strings.
     *
     * @param array<int, string> $texts
     * @return array<int, array<int, float>>
     */
    public function embedMany(
        array $texts,
        ?int $tenantId = null
    ): array {
        $texts = array_values(
            array_filter(
                array_map(
                    fn ($text) => is_string($text)
                        ? trim($text)
                        : '',
                    $texts
                ),
                fn ($text) => $text !== ''
            )
        );

        if ($texts === []) {
            return [];
        }

        $apiKey = config(
            'services.openai.api_key'
        );

        if (!$apiKey) {
            throw new RuntimeException(
                'OpenAI API key is not configured.'
            );
        }

        $payload = [
            'model' => config(
                'knowledge.embedding.model',
                'text-embedding-3-small'
            ),

            'input' => $texts,

            'encoding_format' => 'float',
        ];

        $dimensions = (int) config(
            'knowledge.embedding.dimensions',
            1536
        );

        if ($dimensions > 0) {
            $payload['dimensions'] = $dimensions;
        }

        if ($tenantId !== null) {
            $payload['user'] = 'tenant-' . $tenantId;
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(60)
            ->retry(3, 1000)
            ->post(
                'https://api.openai.com/v1/embeddings',
                $payload
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI embedding request failed: '
                . $response->body()
            );
        }

        $responseData = $response->json();

        $embeddingData = collect(
            $responseData['data'] ?? []
        )
            ->sortBy('index')
            ->values();

        if ($embeddingData->count() !== count($texts)) {
            throw new RuntimeException(
                'The number of returned embeddings does not match the number of input texts.'
            );
        }

        return $embeddingData
            ->pluck('embedding')
            ->map(function ($embedding) {
                if (!is_array($embedding)) {
                    throw new RuntimeException(
                        'OpenAI returned an invalid embedding.'
                    );
                }

                return array_map(
                    'floatval',
                    $embedding
                );
            })
            ->all();
    }
}