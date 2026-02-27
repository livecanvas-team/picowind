<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\Compiler\Nodes\Php;
use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
class FilterCallNode extends ExpressionNode
{
    public function __construct(public ExpressionNode $expr, public Php\FilterNode $filter, public ?Position $position = null)
    {
    }
    public function print(PrintContext $context): string
    {
        return $this->filter->printSimple($context, $this->expr->print($context));
    }
    public function &getIterator(): \Generator
    {
        yield $this->expr;
        yield $this->filter;
    }
}
