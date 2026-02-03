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
use Timber\Timber;

use function Picowind\render;

#[Service]
class AdvancedCustomFields
{
    public function __construct() {}

    #[Hook('block_type_metadata', 'filter', 2)]
    public function metadata(array $metadata): array
    {
        if (! isset($metadata['file'])) {
            return $metadata;
        }

        // Check if the block's file is within any of the blocks directories
        if (
            strpos($metadata['file'], UtilsTheme::current_dir() . '/blocks') === false && (
                UtilsTheme::is_child_theme()
                && ! str_contains($metadata['file'], UtilsTheme::parent_dir() . '/blocks')
            )
        ) {
            return $metadata;
        }

        if (! str_contains((string) $metadata['name'], 'acf/')) {
            return $metadata;
        }

        if (! isset($metadata['acf'])) {
            $metadata['acf'] = [
                'picowind' => true,
            ];
        }

        return $metadata;
    }

    public static function block_render_callback(array $block, string $content = '', bool $is_preview = false, int $post_id = 0): void
    {
        $context = Timber::context();
        $context['post'] = Timber::get_post();
        $context['block'] = $block;
        $context['fields'] = get_fields();
        $context['content'] = $content;
        $context['is_preview'] = $is_preview;

        render($block['path'] . '/index', $context, $block['render_engine'] ?? null);
    }
}
