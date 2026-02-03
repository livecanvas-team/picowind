<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render\Latte;

use Latte\Extension;

/**
 * Custom Latte extension for Picowind
 */
class LatteExtension extends Extension
{
    /**
     * Returns the list of tags provided by this extension.
     * @return array<string, callable> Map: 'tag-name' => parsing-function
     */
    public function getTags(): array
    {
        return [
            'twig' => [TwigTag::class, 'create'],
            'blade' => [BladeTag::class, 'create'],
        ];
    }
}
