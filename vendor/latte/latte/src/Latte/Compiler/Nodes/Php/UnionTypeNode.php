<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php;

use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Helpers;
class UnionTypeNode extends ComplexTypeNode
{
    public function __construct(
        /** @var array<IdentifierNode|NameNode> */
        public array $types,
        public ?Position $position = null
    )
    {
        (function (IdentifierNode|NameNode ...$args) {
        })(...$types);
    }
    public function print(PrintContext $context): string
    {
        return $context->implode($this->types, '|');
    }
    public function &getIterator(): \Generator
    {
        foreach ($this->types as &$item) {
            yield $item;
        }
        Helpers::removeNulls($this->types);
    }
}
