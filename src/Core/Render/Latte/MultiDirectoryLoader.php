<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render\Latte;

use Latte\Loader;
use Latte\RuntimeException;
use Latte\TemplateNotFoundException;

use function array_pop;
use function end;
use function explode;
use function file_exists;
use function file_get_contents;
use function filemtime;
use function implode;
use function is_file;
use function preg_match;
use function str_starts_with;
use function strtr;
use function time;
use function touch;

use const DIRECTORY_SEPARATOR;

/**
 * Custom Latte loader that supports multiple template directories with fallback
 */
class MultiDirectoryLoader implements Loader
{
    private array $directories;

    public function __construct(array $directories)
    {
        $this->directories = array_map(fn ($dir) => $this->normalizePath(rtrim($dir, '/\\') . '/'), $directories);
    }

    public function getContent(string $fileName): string
    {
        $path = $this->findTemplate($fileName);

        if (null === $path) {
            throw new TemplateNotFoundException("Template '{$fileName}' not found in any directory.");
        }

        $normalizedPath = $this->normalizePath($path);
        $isInAllowedDir = false;
        foreach ($this->directories as $dir) {
            if (str_starts_with($normalizedPath, $dir)) {
                $isInAllowedDir = true;
                break;
            }
        }

        if (! $isInAllowedDir) {
            throw new RuntimeException("Template '$path' is not within the allowed paths.");
        }

        if (! is_file($path)) {
            throw new TemplateNotFoundException("Missing template file '$path'.");
        }

        if ($this->isExpired($fileName, time())) {
            if (@touch($path) === false) {
                trigger_error("File's modification time is in the future. Cannot update it: " . error_get_last()['message'], E_USER_WARNING);
            }
        }

        return file_get_contents($path);
    }

    public function isExpired(string $fileName, int $time): bool
    {
        $path = $this->findTemplate($fileName);

        if (null === $path) {
            return true;
        }

        return @filemtime($path) > $time;
    }

    public function getReferredName(string $fileName, string $referringFileName): string
    {
        if (! preg_match('#/|\\\|[a-z]:|phar:#iA', $fileName)) {
            $fileName = $this->normalizePath($referringFileName . '/../' . $fileName);
        }

        return $fileName;
    }

    public function getUniqueId(string $fileName): string
    {
        $path = $this->findTemplate($fileName);
        return $path ? strtr($path, '/', DIRECTORY_SEPARATOR) : $fileName;
    }

    private function findTemplate(string $fileName): ?string
    {
        $normalizedFileName = $this->normalizePath($fileName);

        if (($fileName[0] === '/' || $fileName[0] === '\\') && file_exists($normalizedFileName)) {
            foreach ($this->directories as $dir) {
                if (str_starts_with($normalizedFileName, $dir)) {
                    return $normalizedFileName;
                }
            }
            return null;
        }

        foreach ($this->directories as $dir) {
            $path = $this->normalizePath($dir . ltrim($fileName, '/\\'));
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        preg_match('#^([a-z]:|phar://.+?/)?(.*)#i', $path, $m);
        $res = [];
        foreach (explode('/', strtr($m[2], '\\', '/')) as $part) {
            if ($part === '..' && $res && end($res) !== '..' && end($res) !== '') {
                array_pop($res);
            } elseif ($part !== '.') {
                $res[] = $part;
            }
        }

        return $m[1] . implode(DIRECTORY_SEPARATOR, $res);
    }
}
