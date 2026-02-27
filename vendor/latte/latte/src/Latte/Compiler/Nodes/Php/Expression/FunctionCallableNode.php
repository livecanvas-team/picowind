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
use const PHP_VERSION_ID;
class FunctionCallableNode extends ExpressionNode
{
    public function __construct(public NameNode|ExpressionNode $name, public ?Position $position = null)
    {
    }
    public function print(PrintContext $context): string
    {
        return PHP_VERSION_ID < 80100 ? $context->memberAsString($this->name) : $context->callExpr($this->name) . '(...)';
    }
    public function &getIterator(): \Generator
    {
        yield $this->name;
    }
}
