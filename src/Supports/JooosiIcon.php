<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Service;

/**
 * Jooosi Icon plugin wrapper service.
 *
 * This service uses Jooosi Icon as the primary icon provider and falls back
 * to the legacy Omni Icon plugin when it is installed and activated.
 *
 * @example
 * // Basic usage
 * $jooosiIcon->get_icon('mdi:home');
 * $jooosiIcon->get_icon('local:my-logo');
 * $jooosiIcon->get_icon('jooosi:windpress');
 *
 * // With attributes
 * $jooosiIcon->get_icon('mdi:home', ['class' => 'icon-large', 'width' => '32', 'height' => '32']);
 */
#[Service(alias: OmniIcon::class)]
class JooosiIcon
{
    /**
     * Plugin and service classes ordered from primary to compatibility.
     *
     * @var array<string, string>
     */
    private const ICON_PLUGINS = [
        'JooosiIcon\Plugin' => 'JooosiIcon\Services\IconService',
        'OmniIcon\Plugin' => 'OmniIcon\Services\IconService',
    ];

    /**
     * Resolve the first installed icon plugin.
     *
     * @return null|string
     */
    private function get_plugin_class(): ?string
    {
        foreach (array_keys(self::ICON_PLUGINS) as $pluginClass) {
            if (class_exists($pluginClass)) {
                return $pluginClass;
            }
        }

        return null;
    }

    /**
     * Check if an icon plugin is installed and activated.
     *
     * @return bool True if an icon plugin is active, false otherwise
     */
    private function is_icon_plugin_active(): bool
    {
        return null !== $this->get_plugin_class();
    }

    /**
     * Get the active icon plugin's IconService instance.
     *
     * @return null|object
     */
    private function get_icon_service(): ?object
    {
        $pluginClass = $this->get_plugin_class();
        if (null === $pluginClass) {
            return null;
        }

        try {
            $plugin = $pluginClass::get_instance();
            $container = $plugin->container();

            return $container->get(self::ICON_PLUGINS[$pluginClass]);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("JooosiIcon: Failed to get IconService - {$e->getMessage()}");
            }

            return null;
        }
    }

    /**
     * Get an icon using Jooosi Icon, with legacy Omni Icon compatibility.
     *
     * @param string $iconName Icon name in format "prefix:icon-name" (e.g., "mdi:home", "local:my-logo", "jooosi:windpress")
     * @param array $attributes Optional HTML attributes to add to the SVG element
     * @return null|string the SVG HTML if exists, or null if not found or no plugin is active
     */
    public function get_icon(string $iconName, array $attributes = []): ?string
    {
        if (! $this->is_icon_plugin_active()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('JooosiIcon: Jooosi Icon is not installed or activated. Legacy Omni Icon compatibility was also not found.');
            }

            return null;
        }

        if (! str_contains($iconName, ':')) {
            return null;
        }

        try {
            $iconService = $this->get_icon_service();
            if (null === $iconService || ! method_exists($iconService, 'get_icon')) {
                return null;
            }

            // Convert boolean and numeric attributes to string representation.
            foreach ($attributes as $key => $value) {
                if (is_bool($value)) {
                    $attributes[$key] = $value ? 'true' : 'false';
                } elseif (is_int($value) || is_float($value)) {
                    $attributes[$key] = (string) $value;
                }
            }

            return $iconService->get_icon($iconName, $attributes);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("JooosiIcon error: {$e->getMessage()}");
            }

            return null;
        }
    }
}
