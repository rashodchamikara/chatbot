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
    public function crawlWebsite(Website $website, int $limit = 100): array
    {
        $startUrl = rtrim($website->domain, '/');

        if (!Str::startsWith($startUrl, ['http://', 'https://'])) {
            $startUrl = 'https://' . $startUrl;
        }

        $visited = [];
        $queue = [$startUrl];

        \Log::info('Crawler started', [
            'website_id' => $website->id,
            'domain' => $website->domain,
            'start_url' => $startUrl,
            'limit' => $limit,
        ]);

        while (!empty($queue)) {
            if (count($visited) >= $limit) {
                break;
            }

            $url = array_shift($queue);
            $url = rtrim($url, '/');

            if (!$this->shouldCrawlUrl($url)) {
                \Log::info('Crawler skipped URL by filter', [
                    'website_id' => $website->id,
                    'url' => $url,
                ]);

                continue;
            }

            if (isset($visited[$url])) {
                continue;
            }

            $visited[$url] = true;

            \Log::info('Crawler visiting URL', [
                'website_id' => $website->id,
                'url' => $url,
            ]);

            try {
                $response = Http::timeout(25)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 ChatBotIndexer/1.0',
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])
                    ->get($url);

                if (!$response->successful()) {
                    \Log::warning('Crawler request failed', [
                        'website_id' => $website->id,
                        'url' => $url,
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                $contentType = strtolower($response->header('Content-Type', ''));
                $bodyStart = strtolower(substr(ltrim($response->body()), 0, 500));

                $isHtml =
                    str_contains($contentType, 'text/html') ||
                    str_contains($contentType, 'application/xhtml+xml') ||
                    str_contains($bodyStart, '<!doctype html') ||
                    str_contains($bodyStart, '<html');

                if (!$isHtml) {
                    \Log::info('Skipping non-HTML URL during crawl', [
                        'website_id' => $website->id,
                        'url' => $url,
                        'content_type' => $contentType,
                        'body_start' => substr($response->body(), 0, 100),
                    ]);

                    continue;
                }

                $html = $response->body();

                if (!mb_check_encoding($html, 'UTF-8')) {
                    $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                }

            } catch (\Throwable $e) {
                \Log::warning('Crawler request exception', [
                    'website_id' => $website->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

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

                \Log::info('Crawler saved knowledge page', [
                    'website_id' => $website->id,
                    'url' => $url,
                    'content_length' => strlen($content),
                ]);
            } else {
                \Log::info('Crawler skipped page because content is too short', [
                    'website_id' => $website->id,
                    'url' => $url,
                    'content_length' => strlen($content),
                ]);
            }

            foreach ($this->extractLinks($html, $startUrl) as $link) {
                $link = rtrim($link, '/');

                if (!isset($visited[$link]) && count($queue) < $limit) {
                    $queue[] = $link;
                }
            }
        }

        \Log::info('Crawler completed', [
            'website_id' => $website->id,
            'visited_count' => count($visited),
            'saved_pages' => $website->knowledgePages()->count(),
        ]);

        return array_keys($visited);
    }
    private function normalizeHost(?string $host): ?string
    {
        if (!$host) {
            return null;
        }

        $host = strtolower(trim($host));

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
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

            if (
                $absolute &&
                $this->normalizeHost(parse_url($absolute, PHP_URL_HOST)) ===
                $this->normalizeHost(parse_url($baseUrl, PHP_URL_HOST))
            ) {
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
    private function shouldCrawlUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        $lowerUrl = strtolower($url);

        if (
            str_starts_with($lowerUrl, 'mailto:') ||
            str_starts_with($lowerUrl, 'tel:') ||
            str_starts_with($lowerUrl, 'javascript:') ||
            str_starts_with($lowerUrl, '#')
        ) {
            return false;
        }

        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        $blockedExtensions = [
            '.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.ico',
            '.pdf', '.zip', '.rar', '.7z',
            '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
            '.mp4', '.mp3', '.avi', '.mov', '.webm',
            '.css', '.js', '.xml',
        ];

        foreach ($blockedExtensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return false;
            }
        }

        $blockedPatterns = [
            '/wp-admin',
            '/admin',
            '/login',
            '/logout',
            '/register',
            '/cart',
            '/checkout',
            '/my-account',
            '/account',
            '/search',
            '?s=',
            '?add-to-cart=',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (str_contains($lowerUrl, $pattern)) {
                return false;
            }
        }

        return true;
    }
}