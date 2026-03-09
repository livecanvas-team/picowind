<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Utils;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function get_stylesheet_directory;
use function get_template_directory;

/**
 * Theme utility functions.
 *
 * @package Picowind
 */
class Theme
{
    /**
     * Template directory names
     */
    public const TEMPLATE_DIRECTORIES = [
        'views',
        'blocks',
        'components',
    ];

    /**
     * Cache directory names
     */
    public const CACHE_DIRECTORIES = [
        'twig' => 'picowind/cache/twig',
        'blade' => 'picowind/cache/blade',
        'latte' => 'picowind/cache/latte',
    ];

    /**
     * Get all template directories with full paths
     *
     * @return array Array of template directory paths
     */
    public static function get_template_directories(): array
    {
        $template_dirs = [];
        $current_dir = self::current_dir();

        // Add current theme directories
        foreach (self::TEMPLATE_DIRECTORIES as $dir_name) {
            $template_dirs[] = $current_dir . '/' . $dir_name;
        }

        // Add parent theme directories if this is a child theme
        if (self::is_child_theme()) {
            $parent_dir = self::parent_dir();
            foreach (self::TEMPLATE_DIRECTORIES as $dir_name) {
                $template_dirs[] = $parent_dir . '/' . $dir_name;
            }
        }

        return $template_dirs;
    }

    /**
     * Get template directory names only
     *
     * @return array Array of template directory names
     */
    public static function get_template_directory_names(): array
    {
        return self::TEMPLATE_DIRECTORIES;
    }

    /**
     * Get cache path for a specific feature
     *
     * @param ?string $name Feature name (e.g., 'twig', 'blade', 'latte')
     * @return string The cache path
     */
    public static function get_cache_path(?string $name = null): string
    {
        $upload_dir = wp_upload_dir()['basedir'];

        if (null === $name) {
            return $upload_dir . '/picowind/cache';
        }

        $cache_version = self::get_child_views_cache_version();

        if (! isset(self::CACHE_DIRECTORIES[$name])) {
            $base_path = $upload_dir . '/picowind/cache/' . $name;
            return '' === $cache_version ? $base_path : $base_path . '/' . $cache_version;
        }

        $base_path = $upload_dir . '/' . self::CACHE_DIRECTORIES[$name];
        return '' === $cache_version ? $base_path : $base_path . '/' . $cache_version;
    }

    private static function get_child_views_cache_version(): string
    {
        if (! self::is_child_theme()) {
            return '';
        }

        $views_dir = rtrim((string) self::child_dir(), '/') . '/views';
        if (! is_dir($views_dir)) {
            return '';
        }

        $hash_source = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($views_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = (string) $file->getPathname();
            $relative_path = substr($path, strlen($views_dir) + 1);
            $hash_source[] = $relative_path . '|' . (string) $file->getMTime() . '|' . (string) $file->getSize();
        }

        sort($hash_source);

        if (empty($hash_source)) {
            return '';
        }

        return 'v-' . substr(sha1(implode(';', $hash_source)), 0, 12);
    }

    public static function is_child_theme(): bool
    {
        return is_child_theme();
    }

    /** Get current (active) theme directory
     *
     * @return string The current theme directory path
     */
    public static function current_dir(): string
    {
        return get_stylesheet_directory();
    }

    /** Get parent theme directory if this is a child theme
     *
     * @return string|null The parent theme directory path or null if not a child theme
     */
    public static function parent_dir(): ?string
    {
        return is_child_theme() ? get_template_directory() : null;
    }

    /** Get child theme directory if this is a child theme
     *
     * @return string|null The child theme directory path or null if not a child theme
     */
    public static function child_dir(): ?string
    {
        return is_child_theme() ? get_stylesheet_directory() : null;
    }
}
