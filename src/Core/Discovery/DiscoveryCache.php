<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Throwable;

final class DiscoveryCache
{
    private FilesystemAdapter $cache;

    public function __construct(
        private readonly DiscoveryCacheStrategy $discoveryCacheStrategy,
    ) {
        $uploadDir = wp_get_upload_dir();
        $cacheDir = $uploadDir['basedir'] . '/picowind/cache/';
        wp_mkdir_p($cacheDir);

        $this->cache = new FilesystemAdapter(
            namespace: 'discovery',
            defaultLifetime: 0,
            directory: $cacheDir,
        );
    }

    public function isEnabled(): bool
    {
        return $this->discoveryCacheStrategy !== DiscoveryCacheStrategy::DISABLED;
    }

    /**
     * @param array<Discovery> $discoveries
     */
    public function store(DiscoveryLocation $discoveryLocation, array $discoveries): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldSkipCache()) {
            return;
        }

        $data = [];
        foreach ($discoveries as $discovery) {
            $data[$discovery::class] = $discovery->getItems()->all();
        }

        $manifest = $this->createManifest($discoveryLocation);
        $cacheKey = $this->getCacheKey($discoveryLocation);

        try {
            $item = $this->cache->getItem($cacheKey);
            $item->set([
                'data' => $data,
                'manifest' => $manifest,
            ]);
            $this->cache->save($item);
        } catch (Throwable $e) {
            $this->logError('Failed to store cache', $e, $discoveryLocation, $cacheKey);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function restore(DiscoveryLocation $discoveryLocation): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if ($this->shouldSkipCache()) {
            return null;
        }

        $cacheKey = $this->getCacheKey($discoveryLocation);

        try {
            $item = $this->cache->getItem($cacheKey);

            if (! $item->isHit()) {
                return null;
            }

            $cached = $item->get();

            if (! is_array($cached)) {
                return null;
            }

            $data = $cached['data'] ?? null;
            $cachedManifest = $cached['manifest'] ?? null;

            if (! is_array($data) || ! is_array($cachedManifest)) {
                return null;
            }

            if ($this->isManifestStale($discoveryLocation, $cachedManifest)) {
                $this->cache->deleteItem($cacheKey);
                return null;
            }

            return $data;
        } catch (Throwable $e) {
            $this->logError('Failed to restore cache', $e, $discoveryLocation, $cacheKey);
            return null;
        }
    }

    public function clear(): void
    {
        try {
            $this->cache->clear();
        } catch (Throwable $e) {
            $this->logError('Failed to clear cache', $e);
        }
    }

    private function shouldSkipCache(): bool
    {
        return false;
        return $this->discoveryCacheStrategy === DiscoveryCacheStrategy::PARTIAL && defined('WP_DEBUG') && WP_DEBUG;
    }

    private function getCacheKey(DiscoveryLocation $discoveryLocation): string
    {
        return md5($discoveryLocation->namespace . $discoveryLocation->path);
    }

    /**
     * @return array{files: array<string, int>, count: int}
     */
    private function createManifest(DiscoveryLocation $discoveryLocation): array
    {
        $files = [];

        if (! is_dir($discoveryLocation->path)) {
            return ['files' => [], 'count' => 0];
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($discoveryLocation->path, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[$file->getPathname()] = $file->getMTime();
                }
            }
        } catch (Throwable $e) {
            $this->logError('Failed to create manifest', $e, $discoveryLocation);
        }

        return [
            'files' => $files,
            'count' => count($files),
        ];
    }

    /**
     * @param array{files: array<string, int>, count: int} $cachedManifest
     */
    private function isManifestStale(DiscoveryLocation $discoveryLocation, array $cachedManifest): bool
    {
        if (! is_dir($discoveryLocation->path)) {
            return false;
        }

        try {
            $currentManifest = $this->createManifest($discoveryLocation);

            if ($currentManifest['count'] !== $cachedManifest['count']) {
                return true;
            }

            foreach ($cachedManifest['files'] as $filePath => $cachedMTime) {
                if (! isset($currentManifest['files'][$filePath])) {
                    return true;
                }

                if ($currentManifest['files'][$filePath] !== $cachedMTime) {
                    return true;
                }
            }

            foreach ($currentManifest['files'] as $filePath => $currentMTime) {
                if (! isset($cachedManifest['files'][$filePath])) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            $this->logError('Failed to check manifest staleness, considering stale', $e, $discoveryLocation);
            return true;
        }
    }

    private function logError(
        string $message,
        Throwable $exception,
        ?DiscoveryLocation $discoveryLocation = null,
        ?string $cacheKey = null,
    ): void {
        if (! defined('WP_DEBUG') || ! WP_DEBUG) {
            return;
        }

        $context = [];
        if (null !== $cacheKey) {
            $context[] = 'cacheKey=' . $cacheKey;
        }
        if (null !== $discoveryLocation) {
            $context[] = 'location=' . $discoveryLocation->path;
        }

        $suffix = '';
        if (! empty($context)) {
            $suffix = ' (' . implode(', ', $context) . ')';
        }

        error_log('Picowind Discovery Cache: ' . $message . $suffix . ': ' . $exception->getMessage());
    }
}
