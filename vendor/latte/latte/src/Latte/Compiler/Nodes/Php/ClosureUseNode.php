<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php;

use PicowindDeps\Latte\Compiler\Node;
use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\VariableNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
class ClosureUseNode extends Node
{
    public function __construct(public VariableNode $var, public bool $byRef = \false, public ?Position $position = null)
    {
    }
    public function print(PrintContext $context): string
    {
        return ($this->byRef ? '&' : '') . $this->var->print($context);
    }
    public function &getIterator(): \Generator
    {
        yield $this->var;
    }
}
