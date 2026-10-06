#!/usr/bin/env php
<?php
/**
 * Translation Extraction Script for Coolify Fork
 *
 * Scans Blade views and Livewire PHP classes for hardcoded English text,
 * generates translation keys, and outputs en.json / zh-cn.json skeletons.
 *
 * Usage:
 *   php scripts/extract-i18n.php --batch=navbar
 *   php scripts/extract-i18n.php --batch=dashboard
 *   php scripts/extract-i18n.php --batch=projects
 *   php scripts/extract-i18n.php --all
 *
 * Batches:
 *   navbar    => resources/views/components/navbar.blade.php
 *   dashboard => resources/views/livewire/dashboard/**/*.blade.php
 *   projects  => resources/views/livewire/project/**/*.blade.php
 *   all       => all views under resources/views/livewire/ + components/navbar.blade.php
 *
 * Output:
 *   .trae/documents/i18n-extracted-{batch}.json   => review mapping
 *   lang/en.json   => appended source strings
 *   lang/zh-cn.json => appended empty translations
 */

$opts = getopt('', ['batch:', 'all']);

if (isset($opts['all'])) {
    $batch = 'all';
} elseif (! empty($opts['batch'])) {
    $batch = $opts['batch'];
} else {
    echo "Usage: php scripts/extract-i18n.php --batch=<name> | --all\n";
    echo "Batches: navbar, dashboard, projects, all\n";
    exit(1);
}

$baseDir = dirname(__DIR__);

// Define batch file patterns
$batches = [
    'navbar' => [
        'resources/views/components/navbar.blade.php',
    ],
    'dashboard' => [
        'resources/views/livewire/dashboard/**/*.blade.php',
        'resources/views/livewire/dashboard/*.blade.php',
    ],
    'projects' => [
        'resources/views/livewire/project/**/*.blade.php',
        'resources/views/livewire/project/*.blade.php',
    ],
];

$files = [];
if ($batch === 'all') {
    $files = array_merge(
        glob("{$baseDir}/resources/views/components/navbar.blade.php"),
        glob_recursive("{$baseDir}/resources/views/livewire", '*.blade.php'),
    );
} elseif (isset($batches[$batch])) {
    foreach ($batches[$batch] as $pattern) {
        if (str_contains($pattern, '**')) {
            $dir = dirname(str_replace('/**', '', $pattern));
            $ext = basename($pattern);
            $files = array_merge($files, glob_recursive("{$baseDir}/{$dir}", $ext));
        } else {
            $files = array_merge($files, glob("{$baseDir}/{$pattern}"));
        }
    }
} else {
    echo "Unknown batch: {$batch}\n";
    exit(1);
}

$files = array_unique(array_filter($files, 'file_exists'));
sort($files);

if (empty($files)) {
    echo "No files found for batch '{$batch}'.\n";
    exit(1);
}

echo "Processing " . count($files) . " file(s) for batch: {$batch}\n";

// Translation storage
$translations = [];
$stats = ['text_nodes' => 0, 'attributes' => 0, 'skipped' => 0];

foreach ($files as $file) {
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    $viewPrefix = view_prefix($relPath);
    $content = file_get_contents($file);
    $original = $content;

    // 1. Extract text nodes (text between HTML tags, skipping script/style/php blocks)
    $content = extract_text_nodes($content, $viewPrefix, $translations, $stats);

    // 2. Extract attribute values: title=, placeholder=, label=, aria-label=
    $content = extract_attributes($content, $viewPrefix, $translations, $stats);

    // If modified, write back (dry-run by default)
    // TODO: add --apply flag to enable replacement
    // if ($content !== $original && isset($opts['apply'])) {
    //     file_put_contents($file, $content);
    // }
}

echo "\nStats: text_nodes={$stats['text_nodes']}, attributes={$stats['attributes']}, skipped={$stats['skipped']}\n";

// Load existing translations
$enPath = "{$baseDir}/lang/en.json";
$zhPath = "{$baseDir}/lang/zh-cn.json";
$en = file_exists($enPath) ? json_decode(file_get_contents($enPath), true) ?: [] : [];
$zh = file_exists($zhPath) ? json_decode(file_get_contents($zhPath), true) ?: [] : [];

// Merge new translations
foreach ($translations as $key => $meta) {
    if (! isset($en[$key])) {
        $en[$key] = $meta['source'];
        $zh[$key] = ''; // placeholder for manual translation
    }
}

// Write updated translation files
ksort($en);
ksort($zh);

file_put_contents($enPath, json_encode($en, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
file_put_contents($zhPath, json_encode($zh, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

// Write extracted mapping for review
$reviewPath = "{$baseDir}/.trae/documents/i18n-extracted-{$batch}.json";
file_put_contents($reviewPath, json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

echo "Updated:\n  {$enPath}\n  {$zhPath}\n  {$reviewPath}\n";

/* ===================================================================
   Helper functions
   =================================================================== */

function glob_recursive(string $dir, string $pattern): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function view_prefix(string $relPath): string
{
    // e.g. resources/views/components/navbar.blade.php => navbar
    // e.g. resources/views/livewire/dashboard/index.blade.php => dashboard.index
    $relPath = str_replace('resources/views/', '', $relPath);
    $relPath = str_replace('.blade.php', '', $relPath);
    $relPath = str_replace(['/', '\\'], '.', $relPath);

    return $relPath;
}

function extract_text_nodes(string $content, string $prefix, array &$translations, array &$stats): string
{
    // Strategy: find plain English text nodes inside HTML tags.
    // Skip: blade directives (@if, @foreach...), script tags, x-text, wire:click, etc.
    // We use a simplified regex approach.

    // Pattern: >SOME_TEXT< where SOME_TEXT is plain English text (contains letters)
    // and is not blade syntax, not inside script/style.
    $pattern = '/>([^<\n]{2,200})</';
    $counter = 0;

    $content = preg_replace_callback($pattern, function ($matches) use ($prefix, &$translations, &$stats, &$counter) {
        $text = trim($matches[1]);

        // Skip if empty, numeric, blade echo, or already translated
        if (empty($text) || is_numeric($text) || str_starts_with($text, '{{') || str_starts_with($text, '{!!')) {
            $stats['skipped']++;

            return $matches[0];
        }

        // Skip if no English letters (already Chinese, symbols only)
        if (! preg_match('/[a-zA-Z]/', $text)) {
            $stats['skipped']++;

            return $matches[0];
        }

        // Skip common non-translatable patterns
        $skipPatterns = [
            '/^\s*$/',
            '/^\d+$/',
            '/^[\s\-_:]+$/',
            '/^(\s*\|\s*)+$/',
            '/^\s*([\w\-]+\.)+[a-z]{2,}\s*$/', // domain-like
        ];
        foreach ($skipPatterns as $sp) {
            if (preg_match($sp, $text)) {
                $stats['skipped']++;

                return $matches[0];
            }
        }

        $counter++;
        $key = "{$prefix}.text_{$counter}";
        $translations[$key] = [
            'source' => $text,
            'type' => 'text_node',
            'context' => str_limit($text),
        ];
        $stats['text_nodes']++;

        return '>{{ __("' . $key . '") }}<';
    }, $content);

    return $content;
}

function extract_attributes(string $content, string $prefix, array &$translations, array &$stats): string
{
    $attrs = ['title', 'placeholder', 'label', 'aria-label'];
    $counter = 0;

    foreach ($attrs as $attr) {
        // Match attr="..." or attr='...' containing plain English text
        $pattern = '/\b' . preg_quote($attr, '/') . '=["\']([^"\']{2,200})["\']/';
        $content = preg_replace_callback($pattern, function ($matches) use ($prefix, $attr, &$translations, &$stats, &$counter) {
            $text = $matches[1];

            // Skip blade/php syntax inside attributes
            if (str_starts_with($text, '{{') || str_starts_with($text, '{!!') || str_starts_with($text, '@')) {
                $stats['skipped']++;

                return $matches[0];
            }

            // Skip if no English letters
            if (! preg_match('/[a-zA-Z]/', $text)) {
                $stats['skipped']++;

                return $matches[0];
            }

            // Skip URL-like, variable-like
            if (preg_match('#^(https?://|/|[\w\-]+\.[a-z]+)#i', $text)) {
                $stats['skipped']++;

                return $matches[0];
            }

            $counter++;
            $key = "{$prefix}.{$attr}_{$counter}";
            $translations[$key] = [
                'source' => $text,
                'type' => 'attribute',
                'attribute' => $attr,
                'context' => str_limit($text),
            ];
            $stats['attributes']++;

            return $attr . '="{{ __("' . $key . '") }}"';
        }, $content);
    }

    return $content;
}

function str_limit(string $text, int $len = 60): string
{
    if (mb_strlen($text) <= $len) {
        return $text;
    }

    return mb_substr($text, 0, $len) . '...';
}
