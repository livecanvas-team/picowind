<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Utils;

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

        if (! isset(self::CACHE_DIRECTORIES[$name])) {
            return $upload_dir . '/picowind/cache/' . $name;
        }

        return $upload_dir . '/' . self::CACHE_DIRECTORIES[$name];
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
