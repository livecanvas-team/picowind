<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\NameNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
class TernaryNode extends ExpressionNode
{
    public function __construct(public ExpressionNode $cond, public ?ExpressionNode $if, public ?ExpressionNode $else, public ?Position $position = null)
    {
    }
    public function print(PrintContext $context): string
    {
        return $context->infixOp($this, $this->cond, ' ?' . ($this->if !== null ? ' ' . $this->if->print($context) . ' ' : '') . ': ', $this->else ?? new NameNode('null'));
    }
    public function &getIterator(): \Generator
    {
        yield $this->cond;
        if ($this->if) {
            yield $this->if;
        }
        if ($this->else) {
            yield $this->else;
        }
    }
}
