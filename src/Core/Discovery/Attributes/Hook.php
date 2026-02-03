<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Hook
{
    public function __construct(
        public readonly string $name,
        public readonly string $type = 'filter',
        public readonly int $priority = 10,
        public readonly int $accepted_args = 1
    ) {
    }
}