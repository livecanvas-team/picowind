<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Controller
{
    public function __construct(
        public string $namespace = 'picowind/v1',
        public string $prefix = '',
        public array $middleware = [],
        public array $aliases = [],
    ) {}
}
