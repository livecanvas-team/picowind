<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Service
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly bool $singleton = true,
        /** @var array<string> */
        public readonly array $tags = [],
        public readonly ?string $alias = null,
        public readonly bool $public = false
    ) {
    }
}