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
 * Omni Icon plugin wrapper service.
 *
 * This service acts as a wrapper for the Omni Icon plugin when it's installed
 * and activated. If the plugin is not available, it returns null for icon requests.
 *
 * @example
 * // Basic usage
 * $omniIcon->get_icon('mdi:home');
 * $omniIcon->get_icon('local:my-logo');
 * $omniIcon->get_icon('omni:windpress');
 *
 * // With attributes
 * $omniIcon->get_icon('mdi:home', ['class' => 'icon-large', 'width' => '32', 'height' => '32']);
 */
#[Service]
class OmniIcon
{
    /**
     * Check if Omni Icon plugin is installed and activated.
     *
     * @return bool True if plugin is active, false otherwise
     */
    private function is_omni_icon_active(): bool
    {
        // Check if the Omni Icon plugin class exists
        return class_exists('\OmniIcon\Plugin');
    }

    /**
     * Get the Omni Icon plugin's IconService instance.
     *
     * @return null|\OmniIcon\Services\IconService
     */
    private function get_icon_service()
    {
        if (! $this->is_omni_icon_active()) {
            return null;
        }

        try {
            $plugin = \OmniIcon\Plugin::get_instance();
            $container = $plugin->container();
            return $container->get(\OmniIcon\Services\IconService::class);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("OmniIcon: Failed to get IconService - {$e->getMessage()}");
            }
            return null;
        }
    }

    /**
     * Get an icon using Omni Icon plugin if available.
     *
     * @param string $iconName Icon name in format "prefix:icon-name" (e.g., "mdi:home", "local:my-logo", "omni:windpress")
     * @param array $attributes Optional HTML attributes to add to the SVG element
     * @return null|string the SVG HTML if exists, or null if couldn't found or plugin not active
     */
    public function get_icon(string $iconName, array $attributes = [])
    {
        // Check if Omni Icon plugin is active
        if (! $this->is_omni_icon_active()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('OmniIcon: Omni Icon plugin is not installed or activated. Please install and activate it to use icon functionality.');
            }
            return null;
        }

        // Validate icon name format
        if (! str_contains($iconName, ':')) {
            return null;
        }

        try {
            $iconService = $this->get_icon_service();
            if ($iconService === null) {
                return null;
            }

            // Convert boolean and numeric attributes to string representation
            foreach ($attributes as $key => $value) {
                if (is_bool($value)) {
                    $attributes[$key] = $value ? 'true' : 'false';
                } elseif (is_int($value) || is_float($value)) {
                    $attributes[$key] = (string) $value;
                }
            }

            // Use Omni Icon plugin's IconService to render the icon
            return $iconService->get_icon($iconName, $attributes);
        } catch (\Exception $e) {
            // Log errors in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("OmniIcon error: {$e->getMessage()}");
            }
            return null;
        }
    }
}
