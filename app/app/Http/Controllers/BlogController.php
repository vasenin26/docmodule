<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    private const SLUG_PATTERN = '/^[a-z0-9-]+$/';

    public function index(): Response
    {
        $blogPath = storage_path('app/blog');

        if (!File::isDirectory($blogPath)) {
            return Inertia::render('blog/Index', [
                'posts' => [],
            ]);
        }

        $posts = collect(File::files($blogPath))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'md')
            ->map(function ($file) {
                $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $slug = $filename;
                $raw = File::get($file->getPathname());
                $parsed = $this->parseFrontMatter($raw);
                $meta = $this->extractMeta($parsed['content'], $parsed['frontmatter']);
                $timestampFromFilename = $this->extractTimestampFromFilename($filename);
                $updatedAt = Carbon::createFromTimestamp($file->getMTime());

                return [
                    'slug' => $slug,
                    'title' => $meta['title'],
                    'description' => $meta['description'],
                    'preview' => $meta['description'],
                    'keywords' => $meta['keywords'],
                    'updated_at' => $updatedAt->toISOString(),
                    'updated_at_human' => $updatedAt->format('Y-m-d'),
                    'published_at' => $meta['published_at'],
                    'published_at_human' => $meta['published_at_human'],
                    'sort_key' => $timestampFromFilename?->timestamp ?? $updatedAt->timestamp,
                ];
            })
            ->sortByDesc('sort_key')
            ->map(function (array $post) {
                unset($post['sort_key']);
                return $post;
            })
            ->values()
            ->all();

        return Inertia::render('blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): Response
    {
        abort_unless(preg_match(self::SLUG_PATTERN, $slug) === 1, 404);

        $blogPath = storage_path('app/blog');
        $filePath = $blogPath . DIRECTORY_SEPARATOR . $slug . '.md';

        abort_unless(File::isFile($filePath), 404);

        $realBlogPath = realpath($blogPath);
        $realFilePath = realpath($filePath);

        abort_unless(
            $realBlogPath !== false && $realFilePath !== false && str_starts_with($realFilePath, $realBlogPath . DIRECTORY_SEPARATOR),
            404
        );

        $raw = File::get($realFilePath);
        $parsed = $this->parseFrontMatter($raw);
        $meta = $this->extractMeta($parsed['content'], $parsed['frontmatter']);

        return Inertia::render('blog/Show', [
            'post' => [
                'slug' => $slug,
                'title' => $meta['title'],
                'description' => $meta['description'],
                'keywords' => $meta['keywords'],
                'canonical' => $meta['canonical'],
                'content' => $parsed['content'],
                'updated_at' => Carbon::createFromTimestamp(filemtime($realFilePath))->toISOString(),
                'updated_at_human' => Carbon::createFromTimestamp(filemtime($realFilePath))->format('Y-m-d'),
                'published_at' => $meta['published_at'],
                'published_at_human' => $meta['published_at_human'],
            ],
        ]);
    }

    private function extractMeta(string $content, array $frontmatter): array
    {
        $lines = preg_split('/\R/u', $content) ?: [];
        $title = null;

        foreach ($lines as $line) {
            if (preg_match('/^#\s+(.+)$/u', trim($line), $matches)) {
                $title = trim($matches[1]);
                break;
            }
        }

        $title = trim((string) ($frontmatter['title'] ?? $title ?? 'Untitled post'));
        $description = trim((string) ($frontmatter['description'] ?? ''));
        $canonical = trim((string) ($frontmatter['canonical'] ?? ''));

        $keywords = [];
        if (isset($frontmatter['keywords']) && is_string($frontmatter['keywords'])) {
            $keywords = array_values(array_filter(array_map('trim', explode(',', $frontmatter['keywords']))));
        }

        if ($description === '') {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? '');
            $description = mb_substr($plain, 0, 180);
        }

        $publishedAt = trim((string) ($frontmatter['published_at'] ?? ''));
        $publishedAtHuman = null;
        if ($publishedAt !== '') {
            try {
                $publishedAtHuman = Carbon::parse($publishedAt)->format('Y-m-d');
            } catch (\Throwable) {
                $publishedAt = '';
            }
        }

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $canonical,
            'published_at' => $publishedAt !== '' ? $publishedAt : null,
            'published_at_human' => $publishedAtHuman,
        ];
    }

    private function parseFrontMatter(string $raw): array
    {
        $lines = preg_split('/\R/u', $raw) ?: [];

        if (count($lines) < 3 || trim($lines[0]) !== '---') {
            return [
                'frontmatter' => [],
                'content' => $raw,
            ];
        }

        $endIndex = null;
        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '---') {
                $endIndex = $i;
                break;
            }
        }

        if ($endIndex === null) {
            return [
                'frontmatter' => [],
                'content' => $raw,
            ];
        }

        $frontmatterLines = array_slice($lines, 1, $endIndex - 1);
        $contentLines = array_slice($lines, $endIndex + 1);
        $frontmatter = [];

        foreach ($frontmatterLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode(':', $trimmed, 2), 2, null);
            if ($value === null) {
                continue;
            }

            $normalizedKey = trim($key);
            $normalizedValue = trim($value, " \t\n\r\0\x0B\"'");
            if ($normalizedKey !== '') {
                $frontmatter[$normalizedKey] = $normalizedValue;
            }
        }

        return [
            'frontmatter' => $frontmatter,
            'content' => implode("\n", $contentLines),
        ];
    }

    private function extractTimestampFromFilename(string $filename): ?Carbon
    {
        if (!preg_match('/^(\d{12}|\d{8})-/', $filename, $matches)) {
            return null;
        }

        $raw = $matches[1];
        $format = strlen($raw) === 12 ? 'YmdHi' : 'Ymd';

        try {
            return Carbon::createFromFormat($format, $raw)->startOfMinute();
        } catch (\Throwable) {
            return null;
        }
    }
}
