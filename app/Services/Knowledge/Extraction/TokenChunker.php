<?PHP
namespace App\Services\Knowledge;

use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;

class TokenChunker
{
    private Encoder $encoder;

    public function __construct()
    {
        $provider = new EncoderProvider();

        $provider->setVocabCache(
            storage_path('app/tiktoken')
        );

        $this->encoder = $provider->get(
            'cl100k_base'
        );
    }

    public function countTokens(
        string $text
    ): int {
        return count(
            $this->encoder->encode($text)
        );
    }

    public function chunk(
        string $text,
        ?int $maxTokens = null,
        ?int $overlapTokens = null
    ): array {
        $maxTokens ??= config(
            'knowledge.chunking.max_tokens'
        );

        $overlapTokens ??= config(
            'knowledge.chunking.overlap_tokens'
        );

        $paragraphs = preg_split(
            '/\R{2,}/u',
            trim($text),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $chunks = [];
        $current = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            $candidate = trim(
                implode(
                    "\n\n",
                    [...$current, $paragraph]
                )
            );

            if (
                $current !== []
                && $this->countTokens($candidate)
                    > $maxTokens
            ) {
                $content = trim(
                    implode(
                        "\n\n",
                        $current
                    )
                );

                $chunks[] = [
                    'content' => $content,
                    'token_count' =>
                        $this->countTokens(
                            $content
                        ),
                ];

                $current = $this->createOverlap(
                    $current,
                    $overlapTokens
                );
            }

            $current[] = $paragraph;
        }

        if ($current !== []) {
            $content = trim(
                implode(
                    "\n\n",
                    $current
                )
            );

            $chunks[] = [
                'content' => $content,
                'token_count' =>
                    $this->countTokens($content),
            ];
        }

        return $chunks;
    }

    private function createOverlap(
        array $paragraphs,
        int $overlapTokens
    ): array {
        $overlap = [];
        $tokenCount = 0;

        for (
            $index = count($paragraphs) - 1;
            $index >= 0;
            $index--
        ) {
            $paragraphTokens =
                $this->countTokens(
                    $paragraphs[$index]
                );

            if (
                $overlap !== []
                && $tokenCount +
                    $paragraphTokens >
                    $overlapTokens
            ) {
                break;
            }

            array_unshift(
                $overlap,
                $paragraphs[$index]
            );

            $tokenCount += $paragraphTokens;
        }

        return $overlap;
    }
}