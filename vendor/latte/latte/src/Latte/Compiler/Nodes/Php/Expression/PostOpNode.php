<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\CompileException;
use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
class PostOpNode extends ExpressionNode
{
    private const Ops = ['++' => 1, '--' => 1];
    public function __construct(public ExpressionNode $var, public string $operator, public ?Position $position = null)
    {
        if (!isset(self::Ops[$this->operator])) {
            throw new \InvalidArgumentException("Unexpected operator '{$this->operator}'");
        }
        $this->validate();
    }
    public function print(PrintContext $context): string
    {
        $this->validate();
        return $context->postfixOp($this, $this->var, $this->operator);
    }
    public function validate(): void
    {
        if (!$this->var->isWritable()) {
            throw new CompileException('Cannot write to the expression: ' . $this->var->print(new PrintContext()), $this->var->position);
        }
    }
    public function &getIterator(): \Generator
    {
        yield $this->var;
    }
}
