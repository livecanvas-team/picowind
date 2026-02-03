<?php

declare(strict_types=1);

namespace Picowind\Api;

use Exception;
use Picowind\Core\Discovery\Attributes\Controller;
use Picowind\Core\Discovery\Attributes\Route;
use Picowind\Utils\Config;
use Symfony\Component\Filesystem\Filesystem;
use WP_REST_Request;
use WP_REST_Response;

#[Controller(namespace: 'picowind/v1', prefix: '/onboarding')]
final class OnboardingController
{
    /**
     * Get onboarding status
     */
    #[Route(
        path: '/status',
        methods: 'GET',
        permission_callback: 'manage_options',
    )]
    public function getStatus(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $isCompleted = Config::get('onboarding.completed', false);
            $childTheme = $this->getActiveChildTheme();

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'completed' => $isCompleted,
                    'hasChildTheme' => ! empty($childTheme),
                    'childTheme' => $childTheme,
                ],
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available bundled child themes
     */
    #[Route(
        path: '/themes',
        methods: 'GET',
        permission_callback: 'manage_options',
    )]
    public function getAvailableThemes(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $themes = $this->getBundledThemes();

            return new WP_REST_Response([
                'success' => true,
                'data' => $themes,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recommended plugins
     */
    #[Route(
        path: '/plugins',
        methods: 'GET',
        permission_callback: 'manage_options',
    )]
    public function getRecommendedPlugins(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $plugins = $this->getRecommendedPluginsWithStatus();

            return new WP_REST_Response([
                'success' => true,
                'data' => $plugins,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Install a bundled child theme
     */
    #[Route(
        path: '/install-theme',
        methods: 'POST',
        permission_callback: 'manage_options',
        args: [
            'themeId' => [
                'required' => true,
                'type' => 'string',
                'description' => 'The ID of the theme to install',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    )]
    public function installTheme(WP_REST_Request $request): WP_REST_Response
    {
        $themeId = $request->get_param('themeId');

        try {
            $bundledThemes = $this->getBundledThemesCatalog();

            if (! isset($bundledThemes[$themeId])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Theme not found in bundled catalog',
                ], 404);
            }

            $themeData = $bundledThemes[$themeId];
            $installedTheme = $this->findInstalledTheme(wp_get_themes(), $themeData);

            if ($installedTheme) {
                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Theme is already installed',
                    'data' => [
                        'installed' => true,
                        'themeSlug' => $installedTheme->get_stylesheet(),
                        'theme' => $this->stripBundledThemePath($themeData),
                    ],
                ], 200);
            }

            $installResult = $this->installBundledTheme($themeData);

            if (is_wp_error($installResult)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $installResult->get_error_message(),
                ], 500);
            }

            // Store selected theme in config
            Config::set('onboarding.selectedTheme', $themeId);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Theme installed successfully',
                'data' => [
                    'installed' => true,
                    'themeSlug' => $installResult['themeSlug'],
                    'theme' => $this->stripBundledThemePath($themeData),
                ],
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a bundled child theme
     */
    #[Route(
        path: '/activate-theme',
        methods: 'POST',
        permission_callback: 'manage_options',
        args: [
            'themeSlug' => [
                'required' => true,
                'type' => 'string',
                'description' => 'The theme slug to activate',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    )]
    public function activateTheme(WP_REST_Request $request): WP_REST_Response
    {
        $themeSlug = $request->get_param('themeSlug');

        try {
            $activationResult = $this->activateThemeSlug($themeSlug);

            if (is_wp_error($activationResult)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $activationResult->get_error_message(),
                ], 500);
            }

            Config::set('onboarding.selectedTheme', $themeSlug);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Theme activated successfully',
                'data' => [
                    'activated' => true,
                    'themeSlug' => $themeSlug,
                    'activeTheme' => $this->getActiveChildTheme(),
                ],
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Install a recommended plugin
     */
    #[Route(
        path: '/install-plugin',
        methods: 'POST',
        permission_callback: 'manage_options',
        args: [
            'slug' => [
                'required' => true,
                'type' => 'string',
                'description' => 'The plugin slug to install',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    )]
    public function installPlugin(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');

        try {
            $catalog = $this->getRecommendedPluginsCatalog();
            $plugin = $catalog[$slug] ?? null;

            if (! $plugin) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Plugin is not available for installation',
                ], 404);
            }

            if (! $this->canInstallRecommendedPlugin($plugin)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Plugin is not available for installation',
                ], 404);
            }

            $existingFile = $this->getPluginFileBySlug($slug);

            if ($existingFile) {
                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Plugin is already installed',
                    'data' => [
                        'installed' => true,
                        'active' => $this->isPluginActive($existingFile),
                    ],
                ], 200);
            }

            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';

            $pluginInfo = plugins_api('plugin_information', [
                'slug' => $slug,
                'fields' => [
                    'sections' => false,
                    'versions' => false,
                ],
            ]);

            if (is_wp_error($pluginInfo)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $pluginInfo->get_error_message(),
                ], 500);
            }

            $skin = new \WP_Ajax_Upgrader_Skin();
            $upgrader = new \Plugin_Upgrader($skin);
            $result = $upgrader->install($pluginInfo->download_link);

            if (is_wp_error($result)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $result->get_error_message(),
                ], 500);
            }

            if (! $result) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Plugin installation failed',
                ], 500);
            }

            $pluginFile = $this->getPluginFileBySlug($slug);

            if (! $pluginFile) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Plugin installation completed, but plugin file could not be found',
                ], 500);
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Plugin installed successfully',
                'data' => [
                    'installed' => true,
                    'active' => $this->isPluginActive($pluginFile),
                ],
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a recommended plugin
     */
    #[Route(
        path: '/activate-plugin',
        methods: 'POST',
        permission_callback: 'manage_options',
        args: [
            'slug' => [
                'required' => true,
                'type' => 'string',
                'description' => 'The plugin slug to activate',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    )]
    public function activatePlugin(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');

        try {
            $catalog = $this->getRecommendedPluginsCatalog();
            $plugin = $catalog[$slug] ?? null;

            if (! $plugin) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Plugin is not available for activation',
                ], 404);
            }

            if (! $this->canInstallRecommendedPlugin($plugin)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Plugin is not available for activation',
                ], 404);
            }

            $pluginFile = $this->getPluginFileBySlug($slug);

            if (! $pluginFile) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Plugin is not installed',
                ], 404);
            }

            if ($this->isPluginActive($pluginFile)) {
                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Plugin is already active',
                    'data' => [
                        'installed' => true,
                        'active' => true,
                    ],
                ], 200);
            }

            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            $activationResult = activate_plugin($pluginFile);

            if (is_wp_error($activationResult)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $activationResult->get_error_message(),
                ], 500);
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Plugin activated successfully',
                'data' => [
                    'installed' => true,
                    'active' => true,
                ],
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a theme by slug
     */
    private function activateThemeSlug(string $themeSlug)
    {
        // Verify theme exists
        $theme = wp_get_theme($themeSlug);

        if (! $theme->exists()) {
            return new \WP_Error('theme_not_found', 'Theme does not exist');
        }

        if (! $this->isPicowindChildTheme($theme)) {
            return new \WP_Error('invalid_theme', 'Theme is not a Picowind child theme');
        }

        // Switch to the new theme
        switch_theme($themeSlug);

        // Verify activation
        if (get_stylesheet() !== $themeSlug) {
            return new \WP_Error('activation_failed', 'Theme activation failed');
        }

        return [
            'success' => true,
            'theme' => $themeSlug,
        ];
    }

    /**
     * Mark onboarding as completed
     */
    #[Route(
        path: '/complete',
        methods: 'POST',
        permission_callback: 'manage_options',
    )]
    public function complete(): WP_REST_Response
    {
        try {
            Config::set('onboarding.completed', true);
            Config::set('onboarding.completedAt', current_time('mysql'));

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Onboarding completed successfully',
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset onboarding status (for testing)
     */
    #[Route(
        path: '/reset',
        methods: 'POST',
        permission_callback: 'manage_options',
    )]
    public function reset(): WP_REST_Response
    {
        try {
            Config::set('onboarding.completed', false);
            Config::set('onboarding.selectedTheme', null);
            Config::set('onboarding.completedAt', null);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Onboarding reset successfully',
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active child theme info
     */
    private function getActiveChildTheme(): ?array
    {
        $currentTheme = wp_get_theme();

        // Check if current theme is a child theme
        if ($currentTheme->parent()) {
            return [
                'name' => $currentTheme->get('Name'),
                'slug' => $currentTheme->get_stylesheet(),
                'version' => $currentTheme->get('Version'),
                'parent' => $currentTheme->parent()->get('Name'),
            ];
        }

        return null;
    }

    /**
     * Get bundled themes metadata keyed by bundle ID
     */
    private function getBundledThemesCatalog(): array
    {
        $bundledDir = trailingslashit(get_template_directory()) . 'child-theme';

        if (! is_dir($bundledDir)) {
            return [];
        }

        $entries = scandir($bundledDir);

        if ($entries === false) {
            return [];
        }

        $entries = array_values(array_filter($entries, static function (string $entry) use ($bundledDir) {
            if ($entry[0] === '.') {
                return false;
            }

            return is_dir($bundledDir . '/' . $entry);
        }));

        $themes = [];

        foreach ($entries as $entry) {
            $themePath = $bundledDir . '/' . $entry;
            $stylePath = $themePath . '/style.css';

            if (! file_exists($stylePath)) {
                continue;
            }

            $data = get_file_data($stylePath, [
                'Name' => 'Theme Name',
                'ThemeURI' => 'Theme URI',
                'Description' => 'Description',
                'Author' => 'Author',
                'Version' => 'Version',
                'Template' => 'Template',
                'TextDomain' => 'Text Domain',
                'Tags' => 'Tags',
            ], 'theme');

            $themes[$entry] = [
                'id' => $entry,
                'path' => $themePath,
                'name' => $data['Name'] ?: $entry,
                'description' => $data['Description'] ?? '',
                'version' => $data['Version'] ?? '',
                'author' => $data['Author'] ?? '',
                'themeUri' => $data['ThemeURI'] ?? '',
                'template' => $data['Template'] ?? '',
                'textDomain' => $data['TextDomain'] ?? '',
                'tags' => $this->parseThemeTags($data['Tags'] ?? ''),
            ];
        }

        return $themes;
    }

    /**
     * Recommended plugins catalog keyed by slug
     */
    private function getRecommendedPluginsCatalog(): array
    {
        return [
            'windpress' => [
                'id' => 'windpress',
                'name' => 'WindPress',
                'slug' => 'windpress',
                'source' => 'wporg',
                'url' => 'https://wordpress.org/plugins/windpress/',
                'description' => 'Tailwind CSS integration for the block editor, page builders, and themes.',
            ],
            'omni-icon' => [
                'id' => 'omni-icon',
                'name' => 'Omni Icon',
                'slug' => 'omni-icon',
                'source' => 'wporg',
                'url' => 'https://wordpress.org/plugins/omni-icon/',
                'description' => 'Unified icon management with Iconify, local uploads, and bundled icons.',
            ],
            'livecanvas' => [
                'id' => 'livecanvas',
                'name' => 'LiveCanvas',
                'slug' => 'livecanvas',
                'source' => 'external',
                'url' => 'https://livecanvas.com/',
                'description' => 'Professional page builder for WordPress with front-end editing.',
            ],
        ];
    }

    /**
     * Get recommended plugins with installed and active status
     */
    private function getRecommendedPluginsWithStatus(): array
    {
        $catalog = $this->getRecommendedPluginsCatalog();
        $plugins = [];

        foreach ($catalog as $plugin) {
            $pluginFile = $this->getPluginFileBySlug($plugin['slug']);
            $plugins[] = array_merge($plugin, [
                'installed' => $pluginFile !== null,
                'active' => $pluginFile ? $this->isPluginActive($pluginFile) : false,
            ]);
        }

        return array_values($plugins);
    }

    private function getPluginFileBySlug(string $slug): ?string
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugins = get_plugins();

        foreach ($plugins as $file => $data) {
            $directory = dirname($file);

            if ($directory === $slug || basename($file, '.php') === $slug) {
                return $file;
            }
        }

        foreach ($plugins as $file => $data) {
            if (! empty($data['TextDomain']) && $data['TextDomain'] === $slug) {
                return $file;
            }
        }

        return null;
    }

    private function canInstallRecommendedPlugin(array $plugin): bool
    {
        $source = $plugin['source'] ?? '';
        $slug = $plugin['slug'] ?? '';

        return $source === 'wporg' || $slug === 'livecanvas';
    }

    private function isPluginActive(string $pluginFile): bool
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        return is_plugin_active($pluginFile);
    }

    /**
     * Get bundled themes with install and active status
     */
    private function getBundledThemes(): array
    {
        $bundledThemes = $this->getBundledThemesCatalog();
        $installedThemes = wp_get_themes();
        $activeSlug = wp_get_theme()->get_stylesheet();
        $themes = [];

        foreach ($bundledThemes as $theme) {
            $installedTheme = $this->findInstalledTheme($installedThemes, $theme);
            $installedSlug = $installedTheme ? $installedTheme->get_stylesheet() : null;

            $themes[] = array_merge($this->stripBundledThemePath($theme), [
                'installed' => (bool) $installedTheme,
                'active' => $installedTheme ? $installedSlug === $activeSlug : false,
                'installedSlug' => $installedSlug,
            ]);
        }

        usort($themes, static fn (array $left, array $right) => strcasecmp($left['name'], $right['name']));

        return $themes;
    }

    /**
     * Install a bundled child theme by copying it into the themes directory
     */
    private function installBundledTheme(array $bundledTheme)
    {
        $sourcePath = $bundledTheme['path'] ?? '';

        if ($sourcePath === '' || ! is_dir($sourcePath)) {
            return new \WP_Error('bundle_missing', 'Bundled theme directory not found');
        }

        if (! empty($bundledTheme['template']) && $bundledTheme['template'] !== 'picowind') {
            return new \WP_Error('invalid_template', 'Bundled theme template must be picowind');
        }

        $baseSlug = $this->resolveBundledThemeSlug($bundledTheme);
        $themeSlug = $this->generateUniqueThemeSlug($baseSlug);
        $destination = trailingslashit(get_theme_root()) . $themeSlug;

        $filesystem = new Filesystem();

        try {
            $filesystem->mirror($sourcePath, $destination);
        } catch (Exception $e) {
            return new \WP_Error('installation_failed', $e->getMessage());
        }

        wp_clean_themes_cache();
        $theme = wp_get_theme($themeSlug);

        if (! $theme->exists()) {
            return new \WP_Error('theme_not_found', 'Theme installation failed');
        }

        return [
            'success' => true,
            'themeSlug' => $themeSlug,
        ];
    }

    /**
     * Find an installed theme matching a bundled theme
     */
    private function findInstalledTheme(array $installedThemes, array $bundledTheme): ?\WP_Theme
    {
        $textDomain = $bundledTheme['textDomain'] ?? '';
        $name = $bundledTheme['name'] ?? '';
        $fallbackSlug = $this->resolveBundledThemeSlug($bundledTheme);

        $activeTheme = wp_get_theme();

        if ($this->isPicowindChildTheme($activeTheme)) {
            if ($textDomain !== '' && $activeTheme->get('TextDomain') === $textDomain) {
                return $activeTheme;
            }

            if ($name !== '' && $activeTheme->get('Name') === $name) {
                return $activeTheme;
            }
        }

        if ($textDomain !== '') {
            foreach ($installedThemes as $theme) {
                if ($theme->get('TextDomain') === $textDomain && $this->isPicowindChildTheme($theme)) {
                    return $theme;
                }
            }
        }

        if ($fallbackSlug !== '' && isset($installedThemes[$fallbackSlug])) {
            $candidate = $installedThemes[$fallbackSlug];

            if ($this->isPicowindChildTheme($candidate)) {
                return $candidate;
            }
        }

        if ($name !== '') {
            foreach ($installedThemes as $theme) {
                if ($theme->get('Name') === $name && $this->isPicowindChildTheme($theme)) {
                    return $theme;
                }
            }
        }

        return null;
    }

    private function stripBundledThemePath(array $bundledTheme): array
    {
        unset($bundledTheme['path']);

        return $bundledTheme;
    }

    private function resolveBundledThemeSlug(array $bundledTheme): string
    {
        $slugSource = $bundledTheme['textDomain'] ?? '';

        if ($slugSource === '') {
            $slugSource = $bundledTheme['name'] ?? '';
        }

        if ($slugSource === '') {
            $slugSource = $bundledTheme['id'] ?? '';
        }

        $slug = sanitize_title($slugSource);

        if ($slug === '') {
            $slug = sanitize_title($bundledTheme['id'] ?? 'picowind-child');
        }

        return $slug === '' ? 'picowind-child' : $slug;
    }

    private function generateUniqueThemeSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (is_dir(trailingslashit(get_theme_root()) . $slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function parseThemeTags(string $tags): array
    {
        if ($tags === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $tags))));
    }

    private function isPicowindChildTheme(\WP_Theme $theme): bool
    {
        if (! $theme->exists()) {
            return false;
        }

        return $theme->get_template() === 'picowind' && $theme->get_stylesheet() !== 'picowind';
    }
}
