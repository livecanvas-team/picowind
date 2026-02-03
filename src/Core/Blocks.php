<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core;

use DirectoryIterator;
use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;

/**
 * Handle custom blocks registration and management.
 *
 * @package Picowind
 */
#[Service]
class Blocks
{
    public array $supported_namespaces = ['acf', 'blockstudio', 'picowind'];

    public array $supported_render_engines = ['twig', 'blade', 'php'];

    public array $blocks_dir = [];

    public function __construct() {}

    /**
     * Register custom blocks from the `/blocks` directory.
     */
    #[Hook('init', 'action')]
    public function register(): void
    {
        $blocks = [];

        foreach (UtilsTheme::get_template_directories() as $dir_path) {
            if (! str_ends_with((string) $dir_path, '/blocks')) {
                continue;
            }

            if (! is_dir((string) $dir_path)) {
                continue;
            }

            foreach (new DirectoryIterator($dir_path) as $dir) {
                if ($dir->isDot()) {
                    continue;
                }

                if (file_exists($dir->getPathname() . '/block.json')) {
                    $block_json = json_decode(file_get_contents($dir->getPathname() . '/block.json'), true);

                    do_action('a!picowind/blocks/register:before', $block_json, $dir->getPathname());

                    $shouldRegister = apply_filters('f!picowind/blocks/register:should-register', true, $block_json, $dir->getPathname());
                    if (! $shouldRegister) {
                        continue;
                    }

                    $blocks[] = $dir->getPathname();
                }
            }
        }

        foreach ($blocks as $block) {
            // TODO: include the blocks' functions.php if exists in the same dir
            register_block_type($block);
        }
    }

    #[Hook('block_type_metadata', 'filter', 1)]
    public function metadata(array $metadata): array
    {
        if (! isset($metadata['file'])) {
            return $metadata;
        }

        $in_blocks_dir = false;
        foreach (UtilsTheme::get_template_directories() as $dir_path) {
            if (str_contains($metadata['file'], (string) $dir_path)) {
                $in_blocks_dir = true;
                break;
            }
        }

        if (! $in_blocks_dir) {
            return $metadata;
        }

        // add `picowind/` namespace as default if not set
        if (! str_contains((string) $metadata['name'], '/')) {
            $metadata['name'] = 'picowind/' . $this->name_slugify($metadata['name']);
        }

        [$namespace, $name] = explode('/', (string) $metadata['name'], 2);

        if (! in_array($namespace, $this->supported_namespaces, true)) {
            return $metadata;
        }

        if (! isset($metadata['render'])) {
            if (in_array($namespace, $this->supported_namespaces, true)) {
                $metadata['renderEngine'] = 'twig';
            }
        } elseif (in_array($metadata['render'], $this->supported_render_engines, true)) {
            $metadata['renderEngine'] = $metadata['render'];
            unset($metadata['render']);
        }

        return $metadata;
    }

    #[Hook('block_type_metadata_settings', 'filter', 1, 2)]
    public function metadata_settings(array $settings, array $metadata): array
    {
        if (! isset($metadata['file'])) {
            return $settings;
        }

        $in_blocks_dir = false;
        foreach (UtilsTheme::get_template_directories() as $dir_path) {
            if (str_contains($metadata['file'], (string) $dir_path)) {
                $in_blocks_dir = true;
                break;
            }
        }

        if (! $in_blocks_dir) {
            return $settings;
        }

        [$namespace, $name] = explode('/', (string) $metadata['name'], 2);

        if (! in_array($namespace, $this->supported_namespaces, true)) {
            return $settings;
        }

        if (isset($metadata['renderEngine']) && in_array($metadata['renderEngine'], $this->supported_render_engines, true)) {
            $settings['render_engine'] = $metadata['renderEngine'];

            if ('acf' === $namespace) {
                $settings['render_callback'] = \Picowind\Supports\AdvancedCustomFields::block_render_callback(...);
            }
        }

        return $settings;
    }

    /**
     * Returns a slug friendly string.
     *
     * @see     acf_slugify()
     * @param   string $str  The string to convert.
     * @param   string $glue The glue between each slug piece.
     * @return  string
     */
    private function name_slugify($str = '', $glue = '-')
    {
        $raw = $str;
        $slug = str_replace(['_', '-', '/', ' '], $glue, strtolower(remove_accents($raw)));
        $slug = preg_replace('/[^A-Za-z0-9' . preg_quote($glue) . ']/', '', $slug);

        /**
         * Filters the slug created by name_slugify().
         *
         * @since 5.11.4
         *
         * @param string $slug The newly created slug.
         * @param string $raw  The original string.
         * @param string $glue The separator used to join the string into a slug.
         */
        return apply_filters('f!picowind/blocks/name:slugify', $slug, $raw, $glue);
    }

    #[Hook('block_categories_all', 'filter')]
    public function add_custom_block_category(array $categories): array
    {
        return array_merge(
            [
                [
                    'slug' => 'picowind',
                    'title' => __('Picowind', 'picowind'),
                ],
            ],
            $categories,
        );
    }
}
