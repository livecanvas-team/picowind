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
use Picowind\Utils\Theme as UtilsTheme;
use Symfony\Component\Finder\Finder;
use Throwable;
use WindPress\WindPress\Core\Volume;
use WindPress\WindPress\Utils\Common;
use WindPress\WindPress\Utils\Config;

#[Service]
class WindPress
{
    public function __construct() {}

    #[Hook('f!windpress/core/cache:compile.providers', 'filter')]
    public function compile_providers(array $providers): array
    {
        $providers[] = [
            'id' => 'picowind',
            'name' => 'picowind Theme',
            'description' => 'Scans the picowind theme & child theme',
            'callback' => $this->provider_callback(...),
            'enabled' => Config::get(
                sprintf(
                    'integration.%s.enabled',
                    'picowind', // The id of this custom provider
                ),
                true,
            ),
            'type' => 'theme',
            'homepage' => 'https://picostrap.com/?ref=windpress',
            'is_installed_active' => static fn () => 1,
        ];

        return $providers;
    }

    public function provider_callback(): array
    {
        // Any files with this extension will be scanned
        $file_extensions = [
            'php',
            'js',
            'twig',
        ];

        $contents = [];

        $finder = new Finder();

        $themeDirs = [UtilsTheme::current_dir()];
        if (UtilsTheme::is_child_theme()) {
            $themeDirs[] = UtilsTheme::parent_dir();
        }

        // Scan the theme directory according to the file extensions
        foreach ($file_extensions as $file_extension) {
            $finder
                ->files()
                ->in($themeDirs)
                ->notPath('vendor')
                ->name('*.' . $file_extension);
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

    #[Hook('f!windpress/core/volume:get_entries.entries', 'filter')]
    public function sfs_handler_get(array $sfs_entries): array
    {
        $finder = new Finder();
        $entries = [];
        $existing_dirs = [];

        $asset_paths = [
            UtilsTheme::current_dir() . '/public/styles',
        ];

        if (UtilsTheme::is_child_theme()) {
            $asset_paths[] = UtilsTheme::parent_dir() . '/public/styles';
        }

        foreach ($asset_paths as $asset_path) {
            if (file_exists($asset_path)) {
                $existing_dirs[] = $asset_path;
            }
        }

        if ([] === $existing_dirs) {
            return $entries;
        }

        $finder
            ->ignoreUnreadableDirs()
            ->in($existing_dirs)
            ->files()
            ->followLinks()
            ->name(['*.css', '*.js']);

        do_action('a!picowind/supports/windpress:get_entries.finder', $finder);

        foreach ($finder as $file) {
            if (! is_readable($file->getPathname())) {
                continue;
            }

            // Detect directory based on file path
            $file_path = $file->getPathname();

            if (UtilsTheme::is_child_theme() && str_starts_with($file_path, UtilsTheme::current_dir())) {
                $handler = 'picowind-child';
                $relative_path = '@picowind/' . $file->getRelativePathname();
            } elseif (str_starts_with($file_path, UtilsTheme::is_child_theme() ? UtilsTheme::parent_dir() : UtilsTheme::current_dir())) {
                $handler = 'picowind-parent';
                $relative_path = '@picowind/' . $file->getRelativePathname();
            } else {
                continue;
            }

            // Child theme files take precedence over parent theme files.
            if ($handler === 'picowind-parent' && isset($entries[$relative_path])) {
                continue;
            }

            $entries[$relative_path] = [
                'name' => $file->getFilename(),
                'relative_path' => $relative_path,
                'content' => $file->getContents(),
                'handler' => $handler,
                'signature' => wp_create_nonce(sprintf('%s:%s', $handler, $file->getRelativePathname())),
                // 'readonly' => true,
                'path_on_disk' => $file->getPathname(),
            ];
        }

        return array_merge($sfs_entries, array_values($entries));
    }

    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-child', 'action')]
    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-parent', 'action')]
    public function sfs_handler_save(array $entry): void
    {
        if (! isset($entry['handler'])) {
            return;
        }

        $handler = $entry['handler'];
        if ('picowind-child' === $handler) {
            if (! UtilsTheme::is_child_theme()) {
                return; // No child theme to save to
            }
            $data_dir = UtilsTheme::current_dir() . '/public/styles';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind/'));
        } elseif ('picowind-parent' === $handler) {
            $data_dir = UtilsTheme::is_child_theme()
                ? UtilsTheme::parent_dir() . '/public/styles'
                : UtilsTheme::current_dir() . '/public/styles';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind/'));
        } else {
            return; // Unknown handler
        }

        // if the signature is not set, it is a new entry.
        if (! isset($entry['signature'])) {
            // sanitize the file name.
            add_filter('sanitize_file_name_chars', [Volume::class, 'sanitize_file_name_chars'], 10, 2);
            // split the path, and sanitize each part.
            $_relativePath = implode('/', array_map('sanitize_file_name', explode('/', $_relativePath)));
            $_relativePath = sanitize_file_name($_relativePath);
            remove_filter('sanitize_file_name_chars', [Volume::class, 'sanitize_file_name_chars'], 10);
            $entry['name'] = pathinfo($_relativePath, PATHINFO_BASENAME);

            // only handle a css and js files.
            if (! in_array(pathinfo($entry['name'], PATHINFO_EXTENSION), ['css', 'js'], true)) {
                return;
            }

            $entry['signature'] = wp_create_nonce(sprintf('%s:%s', $handler, $_relativePath));
        }

        // verify the signature
        if (! wp_verify_nonce($entry['signature'], sprintf('%s:%s', $handler, $_relativePath))) {
            return;
        }

        try {
            // if the content is empty, delete the file.
            if (! isset($entry['content']) || '' === $entry['content']) {
                Common::delete_file($data_dir . '/' . $_relativePath);
            } else {
                Common::save_file($entry['content'], $data_dir . '/' . $_relativePath);
            }
        } catch (Throwable $throwable) {
            if (WP_DEBUG_LOG) {
                error_log($throwable->__toString());
            }
        }
    }

    #[Hook('f!windpress/core/volume:get_available_handlers', 'filter')]
    public function sfs_handler_list(array $handlers): array
    {
        $handlers[] = [
            'value' => 'picowind-child',
            'label' => __('Picowind Child Theme', 'picowind'),
            'description' => __('Manage files within the Picowind child theme', 'picowind'),
        ];

        if (UtilsTheme::is_child_theme()) {
            $handlers[] = [
                'value' => 'picowind-parent',
                'label' => __('Picowind Parent Theme', 'picowind'),
                'description' => __('Manage files within the Picowind parent theme', 'picowind'),
            ];
        }

        return $handlers;
    }

    /**
     * Set up the theme default settings upon activation.
     */
    #[Hook('after_switch_theme', type: 'action')]
    public function after_switch_theme(): void
    {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $is_require_reload = false;
        // Require the WindPress plugin to be active
        if (! is_plugin_active('windpress/windpress.php')) {
            if (! file_exists(WP_PLUGIN_DIR . '/windpress/windpress.php')) {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/misc.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                $api = plugins_api('plugin_information', array(
                    'slug' => 'windpress',
                    'fields' => array(
                        'short_description' => false,
                        'sections' => false,
                        'requires' => false,
                        'rating' => false,
                        'ratings' => false,
                        'downloaded' => false,
                        'last_updated' => false,
                        'added' => false,
                        'tags' => false,
                        'compatibility' => false,
                        'homepage' => false,
                        'donate_link' => false,
                    ),
                ));
                if (! is_wp_error($api)) {
                    $upgrader = new \Plugin_Upgrader();
                    $installed = $upgrader->install($api->download_link);
                }
            }
            activate_plugin('windpress/windpress.php');
            $is_require_reload = true;
        }
        // Import the Picowind css file into the WindPress `main.css` file
        $main_css_path = WP_CONTENT_DIR . '/uploads/windpress/data/main.css';
        if (! file_exists($main_css_path)) {
            // copy the default main.css file
            $default_main_css_path = WP_CONTENT_DIR . '/plugins/windpress/stubs/tailwindcss-v4/main.css';
            if (file_exists($default_main_css_path)) {
                $wp_filesystem->copy($default_main_css_path, $main_css_path);
            } else {
                return;
            }
        }
        $main_css_content = $wp_filesystem->get_contents($main_css_path);
        if ($main_css_content === false) {
            return;
        }
        $import_statement = '@import "./@picowind/tailwind.css";';
        if (strpos($main_css_content, $import_statement) === false) {
            $main_css_content .= "\n" . $import_statement . "\n";
        }
        $preflight_statement = '/* @import "tailwindcss/preflight.css" layer(base); */';
        if (strpos($main_css_content, $preflight_statement) !== false) {
            $main_css_content = str_replace(
                $preflight_statement,
                '@import "tailwindcss/preflight.css" layer(base);',
                $main_css_content,
            );
        }
        $wp_filesystem->put_contents($main_css_path, $main_css_content);
        if ($is_require_reload) {
            // reload with js to avoid "Headers already sent" error
            echo '<script>location.reload();</script>';
            exit();
        }
    }
}
