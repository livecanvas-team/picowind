<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\ListNode;
use PicowindDeps\Latte\Compiler\PrintContext;
/**
 * Only for parser needs.
 * @internal
 */
class TemporaryNode extends ExpressionNode
{
    public function __construct(public ListNode|null $value)
    {
    }
    public function print(PrintContext $context): string
    {
    }
    public function &getIterator(): \Generator
    {
        yield;
    }
}
