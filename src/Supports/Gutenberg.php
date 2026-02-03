<?php

declare(strict_types=1);

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Timber\Timber;

#[Service]
class Gutenberg
{
    /**
     * Add the current post to Timber context in the block editor.
     *
     * @param array $context The existing Timber context.
     * @return array The modified Timber context with the current post added.
     */
    #[Hook('f!picowind/context', 'filter')]
    public function editor_post_context(array $context): array
    {
        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && $screen->is_block_editor()) {
                $post_id = get_the_ID();
                if ($post_id) {
                    $context['post'] = Timber::get_post($post_id);
                }
            }
        }

        return $context;
    }
}
