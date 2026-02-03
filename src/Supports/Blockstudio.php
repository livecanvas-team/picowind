<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Blockstudio\Build;
use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;

use function Picowind\render;

#[Service]
class Blockstudio
{
    public function __construct() {}

    #[Hook('blockstudio/settings/users/roles', 'filter')]
    public function editor_access($roles): array
    {
        $roles[] = 'administrator';
        return $roles;
    }

    #[Hook('blockstudio/settings/tailwind/enabled', 'filter')]
    public function disable_tailwind(): bool
    {
        return false;
    }

    #[Hook('f!picowind/blocks/register:should-register', 'filter', 10, 3)]
    public function should_register(bool $should_register, array $block_json, string $dir_path): bool
    {
        if (isset($block_json['blockstudio']) && (true !== $block_json['blockstudio'] || 1 !== $block_json['blockstudio'])) {
            return false;
        }

        return $should_register;
    }

    #[Hook('a!picowind/blocks/register:before', 'action', 10, 2)]
    public function register_blocks(array $block_json, string $dir_path): void
    {
        if (! class_exists(Build::class)) {
            return;
        }

        if (! isset($block_json['blockstudio']) || true !== $block_json['blockstudio'] && 1 !== $block_json['blockstudio']) {
            return;
        }

        Build::init([
            'dir' => $dir_path,
        ]);
    }

    #[Hook('blockstudio/blocks/render', 'filter', 10, 4)]
    public function block_render($value, $block, $isEditor, $isPreview)
    {
        $blockPath = $block->path;

        // only render blocks from the /blocks directory
        $in_blocks_dir = false;
        foreach (UtilsTheme::get_template_directories() as $dir_path) {
            if (str_contains((string) $blockPath, (string) $dir_path)) {
                $in_blocks_dir = true;
                break;
            }
        }

        if (! $in_blocks_dir) {
            return $value;
        }

        // if not a blade or twig file, return the original value
        if (! str_ends_with((string) $blockPath, '.blade.php') && ! str_ends_with((string) $blockPath, '.twig')) {
            return $value;
        }

        $data = $block->blockstudio['data'];

        $rendered = render(
            $blockPath,
            [
                'a' => $data['attributes'],
                'attributes' => $data['attributes'],
                'b' => $data['block'],
                'block' => $data['block'],
                'c' => $data['context'],
                'context' => $data['context'],
                'isPreview' => $isPreview,
                'isEditor' => $isEditor,
            ],
            null,
            false,
        );

        return $rendered;
    }
}
