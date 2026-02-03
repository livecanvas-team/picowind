<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use Throwable;

final class DirectoryScanner
{
    /**
     * @param array<Discovery> $discoveries
     */
    public function __construct(
        private readonly array $discoveries,
    ) {}

    /**
     * Recursively scan a directory and apply discoveries to all files
     *
     * @param array<string, true> $skipFiles
     */
    public function scan(DiscoveryLocation $location, string $path, array $skipFiles = []): void
    {
        $input = realpath($path);

        // Make sure the path is valid
        if (false === $input) {
            return;
        }

        // Directories are scanned recursively
        if (is_dir($input)) {
            // Skip certain directories
            if ($this->shouldSkipDirectory($input)) {
                return;
            }

            $items = scandir($input, SCANDIR_SORT_NONE);
            if (false === $items) {
                return;
            }

            foreach ($items as $subPath) {
                // Skip `.` and `..`
                if ('.' === $subPath || '..' === $subPath) {
                    continue;
                }

                // Scan all files and folders within this directory
                $this->scan($location, "{$input}/{$subPath}", $skipFiles);
            }

            return;
        }

        if ([] !== $skipFiles && isset($skipFiles[$input])) {
            return;
        }

        // At this point, we have a single file
        $pathInfo = pathinfo($input);
        $extension = $pathInfo['extension'] ?? null;
        $fileName = $pathInfo['filename'] ?? null;

        // If this is a PHP file starting with an uppercase letter, we assume it's a class
        if ('php' === $extension && null !== $fileName && ucfirst($fileName) === $fileName) {
            // If namespace is empty, extract from file
            if ('' === $location->namespace) {
                $className = $this->extractClassNameFromFile($input);
            } else {
                $className = $location->toClassName($input);
            }

            if (null === $className) {
                return;
            }

            // Try to create a class reflector
            $classReflector = null;
            try {
                // For non-vendor locations, require the file first
                // This is necessary for child themes that don't have composer autoload
                if (! $location->isVendor() && ! class_exists($className, false)) {
                    require_once $input;
                }

                if (class_exists($className)) {
                    $classReflector = new ClassReflector($className);
                }
            } catch (Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Picowind Discovery Error for ' . $className . ': ' . $e->getMessage());
                }
            }

            // Pass to discoveries
            if (null !== $classReflector) {
                foreach ($this->discoveries as $discovery) {
                    $discovery->discover($location, $classReflector);
                }
                return;
            }
        }

        // If not a class, check if any discovery can handle paths
        foreach ($this->discoveries as $discovery) {
            if ($discovery instanceof DiscoversPath) {
                $discovery->discoverPath($location, $input);
            }
        }
    }

    /**
     * Check whether a given directory should be skipped
     */
    private function shouldSkipDirectory(string $path): bool
    {
        $directory = pathinfo($path, PATHINFO_BASENAME);

        // Skip hidden directories (starting with .)
        if (str_starts_with($directory, '.')) {
            return true;
        }

        return 'node_modules' === $directory || 'vendor' === $directory;
    }

    /**
     * Extract the fully qualified class name from a PHP file
     */
    private function extractClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            return null;
        }

        $namespace = '';
        $className = '';

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            $namespace = trim($namespaceMatches[1]);
        }

        // Extract class name
        $fileBasename = pathinfo($filePath, PATHINFO_FILENAME);
        if (preg_match('/\b(?:class|interface|trait|enum)\s+' . preg_quote($fileBasename, '/') . '\b/', $content)) {
            $className = $fileBasename;
        }

        if ('' === $className) {
            return null;
        }

        return '' === $namespace ? $className : $namespace . '\\' . $className;
    }
}
