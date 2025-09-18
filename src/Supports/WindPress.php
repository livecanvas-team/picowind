<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

use Throwable;
use WindPress\WindPress\Utils\Common;
use WindPress\WindPress\Utils\Config;

use function Picowind\get_symfony_finder;

class WindPress
{
    public function __construct()
    {
        add_filter('f!windpress/core/cache:compile.providers', [$this, 'compile_providers']);
        add_filter('f!windpress/core/volume:get_entries.entries', [$this, 'sfs_handler_get']);
        add_action('a!windpress/core/volume:save_entries.entry.picowind', [$this, 'sfs_handler_save']);
    }

    public function compile_providers(array $providers): array
    {
        $providers[] = [
            'id' => 'picowind',
            'name' => 'picowind Theme',
            'description' => 'Scans the picowind theme & child theme',
            'callback' => [$this, 'provider_callback'],
            'enabled' => Config::get(sprintf(
                'integration.%s.enabled',
                'picowind' // The id of this custom provider
            ), true),
            'type' => 'theme',
            'homepage' => 'https://picostrap.com/?ref=windpress',
            'is_installed_active' => static fn() => 1,
        ];

        return $providers;
    }

    public function provider_callback()
    {
        // Any files with this extension will be scanned
        $file_extensions = [
            'php',
            'js',
            'twig',
        ];

        $contents = [];

        // if the theme is not picowind or its' child, early return
        if (get_template() != 'picowind') {
            return $contents;
        }

        $wpTheme = wp_get_theme();
        $themeDir = $wpTheme->get_stylesheet_directory();

        $finder = get_symfony_finder();
        if (! $finder) {
            return $contents;
        }

        // Check if the current theme is a child theme and get the parent theme directory
        $has_parent = (bool) $wpTheme->parent();
        $parentThemeDir = $wpTheme->parent()->get_stylesheet_directory() ?? null;

        $finder->files()->notPath([
            $has_parent ? $parentThemeDir . '/vendor' : $themeDir . '/vendor',
        ]);

        // Scan the theme directory according to the file extensions
        foreach ($file_extensions as $file_extension) {
            $finder->files()->in($themeDir)->name('*.' . $file_extension);
            if ($has_parent) {
                $finder->files()->in($parentThemeDir)->name('*.' . $file_extension);
            }
        }

        // Get the file contents and send to the compiler
        foreach ($finder as $file) {
            $contents[] = [
                'name' => $file->getRelativePathname(),
                'content' => $file->getContents(),
            ];
        }

        return $contents;
    }

    public function sfs_handler_get(array $sfs_entries): array
    {
        $entries = [];

        $data_dir = get_template_directory() . '/assets/styles';

        if (! file_exists($data_dir)) {
            return $entries;
        }

        $finder = get_symfony_finder();
        if (! $finder) {
            return $entries;
        }

        $finder
            ->ignoreUnreadableDirs()
            ->in($data_dir)
            ->files()
            ->followLinks()
            ->name(['*.css', '*.js']);

        do_action('a!picowind_sfs_handler_get:get_entries.finder', $finder);

        foreach ($finder as $file) {
            if (! is_readable($file->getPathname())) {
                continue;
            }

            $entries[] = [
                'name' => $file->getFilename(),
                'relative_path' => '@picowind/' . $file->getRelativePathname(),
                'content' => $file->getContents(),
                'handler' => 'picowind',
                'signature' => wp_create_nonce(sprintf('%s:%s', 'picowind', $file->getRelativePathname())),
                // 'readonly' => true,
                'path_on_disk' => $file->getPathname(),
            ];
        }

        return array_merge($sfs_entries, $entries);
    }

    public function sfs_handler_save(array $entry): void
    {
        $data_dir = get_template_directory() . '/assets/styles';

        if (! isset($entry['signature'])) {
            return;
        }

        $_relativePath = substr($entry['relative_path'], strlen('@picowind/'));

        // verify the signature
        if (! wp_verify_nonce($entry['signature'], sprintf('%s:%s', 'picowind', $_relativePath))) {
            return;
        }

        try {
            // if the content is empty, delete the file.
            if (empty($entry['content'])) {
                Common::delete_file($data_dir . $_relativePath);
            } else {
                Common::save_file($entry['content'], $data_dir . $_relativePath);
            }
        } catch (Throwable $throwable) {
            if (WP_DEBUG_LOG) {
                error_log($throwable->__toString());
            }
        }
    }
}
