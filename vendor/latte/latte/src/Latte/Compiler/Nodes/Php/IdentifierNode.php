<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php;

use PicowindDeps\Latte\Compiler\Node;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
class IdentifierNode extends Node
{
    public function __construct(public string $name, public ?Position $position = null)
    {
    }
    public function __toString(): string
    {
        return $this->name;
    }
    public function print(PrintContext $context): string
    {
        return $this->name;
    }
    public function &getIterator(): \Generator
    {
        \false && yield;
    }
}
