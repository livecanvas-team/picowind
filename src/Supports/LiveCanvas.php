<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

#[Service]
class LiveCanvas
{
    public function __construct() {}

    #[Hook('admin_menu', 'action', priority: 20)]
    public function add_picowind_submenu(): void
    {
        if (! $this->is_livecanvas_active()) {
            return;
        }

        $parent_slug = $this->get_livecanvas_menu_slug();

        if (! $parent_slug) {
            return;
        }

        add_submenu_page(
            $parent_slug,
            __('Picowind', 'picowind'),
            __('Picowind', 'picowind'),
            'manage_options',
            'themes.php?page=picowind',
            null,
        );
    }

    /**
     * Declare the theme as LiveCanvas friendly
     * @link https://livecanvas.com/faq/which-themes-with-livecanvas/
     */
    public static function lc_theme_is_livecanvas_friendly(): bool
    {
        return true;
    }

    /**
     * Define LiveCanvas editor configuration
     * @link https://github.com/livecanvas-team/picostrap5/blob/0b4e60e32664941261ff3b5be1ba29a7ce2be424/inc/livecanvas-config.php
     */
    public static function lc_define_editor_config($key)
    {
        $data = [
            'config_file_slug' => 'daisyui-5',
        ];

        return $data[$key];
    }

    private function is_livecanvas_active(): bool
    {
        $plugin_file = $this->get_plugin_file_by_slug('livecanvas');

        if (! $plugin_file) {
            return false;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        return is_plugin_active($plugin_file);
    }

    private function get_livecanvas_menu_slug(): ?string
    {
        global $menu;

        if (! is_array($menu)) {
            return null;
        }

        foreach ($menu as $item) {
            if (! is_array($item) || ! isset($item[0], $item[2])) {
                continue;
            }

            $label = wp_strip_all_tags((string) $item[0]);
            $slug = (string) $item[2];

            if ($label === '') {
                continue;
            }

            if (stripos($label, 'livecanvas') !== false || stripos($slug, 'livecanvas') !== false) {
                return $slug;
            }
        }

        return null;
    }

    private function get_plugin_file_by_slug(string $slug): ?string
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugins = get_plugins();

        /**
         * @var string $file
         */
        foreach ($plugins as $file => $data) {
            $directory = dirname($file);

            if ($directory === $slug || basename($file, '.php') === $slug) {
                return $file;
            }

            if (! empty($data['TextDomain']) && $data['TextDomain'] === $slug) {
                return $file;
            }
        }

        return null;
    }
}
