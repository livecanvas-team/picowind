<?php

declare (strict_types=1);
/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */
namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Service;
use JooosiIcon\Services\IconService as JooosiIconService;
use OmniIcon\Services\IconService as OmniIconService;
use JooosiIcon\Plugin as JooosiIconPlugin;
use OmniIcon\Plugin as OmniIconPlugin;
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
#[Service(alias: \Picowind\Supports\OmniIcon::class)]
class JooosiIcon
{
    /**
     * Get an icon using Jooosi Icon, with legacy Omni Icon compatibility.
     *
     * @param string $iconName Icon name in format "prefix:icon-name" (e.g., "mdi:home", "local:my-logo", "jooosi:windpress")
     * @param array $attributes Optional HTML attributes to add to the SVG element
     * @return null|string the SVG HTML if exists, or null if not found or no plugin is active
     */
    public function get_icon(string $iconName, array $attributes = []): ?string
    {
        if (!str_contains($iconName, ':')) {
            return null;
        }
        try {
            if (class_exists(JooosiIconService::class)) {
                $iconService = JooosiIconPlugin::get_instance()->container()->get(JooosiIconService::class);
            } elseif (class_exists(OmniIconService::class)) {
                $iconService = OmniIconPlugin::get_instance()->container()->get(OmniIconService::class);
            } else {
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
            /** @var JooosiIconService|OmniIconService $iconService */
            $sanitized = $iconService->get_icon($iconName, $attributes);
            if ($sanitized === \false || empty($sanitized)) {
                if (defined('WP_DEBUG') && \WP_DEBUG) {
                    error_log("JooosiIcon: failed to retrieve icon '{$iconName}'");
                }
                return null;
            }
            // remove XML declaration if present
            return preg_replace('/\A(?:\xEF\xBB\xBF)?\s*<\?xml(?=\s).*?\?>\s*/is', '', $sanitized);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && \WP_DEBUG) {
                error_log("JooosiIcon error: {$e->getMessage()}");
            }
            return null;
        }
    }
}
