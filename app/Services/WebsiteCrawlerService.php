<?php

namespace App\Services;

use App\Models\Website;
use App\Models\KnowledgePage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class WebsiteCrawlerService
{
    public function crawlWebsite(Website $website,  ?int $limit = null): array
    {
        $startUrl = rtrim($website->domain, '/');

        if (!Str::startsWith($startUrl, ['http://', 'https://'])) {
            $startUrl = 'https://' . $startUrl;
        }

        $visited = [];
        $queue = [$startUrl];

        while (!empty($queue)) {
            if ($limit !== null && count($visited) >= $limit) {
                break;
            }
            $url = array_shift($queue);

            if (isset($visited[$url])) {
                continue;
            }

            $visited[$url] = true;

            try {
                $html = Http::timeout(10)->get($url)->body();
            } catch (\Throwable $e) {
                continue;
            }

            $title = $this->extractTitle($html);
            $content = $this->extractCleanText($html);

            if (strlen($content) > 300) {
                KnowledgePage::updateOrCreate(
                    [
                        'website_id' => $website->id,
                        'url' => $url,
                    ],
                    [
                       'title' => $title,
                        'type' => 'page',
                        'source_type' => 'crawler',
                        'content' => $content,
                        'content_hash' => hash('sha256', $content),
                        'is_indexed' => false,
                        'is_active' => true,
                    ]
                );
            }

            foreach ($this->extractLinks($html, $startUrl) as $link) {
                if (!isset($visited[$link]) && count($queue) < $limit) {
                    $queue[] = $link;
                }
            }
        }

        return array_keys($visited);
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(html_entity_decode($matches[1]));
        }

        return null;
    }

    private function extractCleanText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = strip_tags($html);
        $html = html_entity_decode($html);
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }

    private function extractLinks(string $html, string $baseUrl): array
    {
        $links = [];

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//a[@href]') as $node) {
            $href = $node->getAttribute('href');

            if (!$href || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            $absolute = $this->toAbsoluteUrl($href, $baseUrl);

            if ($absolute && parse_url($absolute, PHP_URL_HOST) === parse_url($baseUrl, PHP_URL_HOST)) {
                $links[] = strtok($absolute, '#');
            }
        }

        return array_values(array_unique($links));
    }

    private function toAbsoluteUrl(string $href, string $baseUrl): ?string
    {
        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }

        if (Str::startsWith($href, '/')) {
            return rtrim($baseUrl, '/') . $href;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }
}