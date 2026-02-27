<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
class ArrayAccessNode extends ExpressionNode
{
    public function __construct(public ExpressionNode $expr, public ?ExpressionNode $index = null, public ?Position $position = null)
    {
    }
    public function print(PrintContext $context): string
    {
        return $context->dereferenceExpr($this->expr) . '[' . ($this->index !== null ? $this->index->print($context) : '') . ']';
    }
    public function &getIterator(): \Generator
    {
        yield $this->expr;
        if ($this->index) {
            yield $this->index;
        }
    }
}
