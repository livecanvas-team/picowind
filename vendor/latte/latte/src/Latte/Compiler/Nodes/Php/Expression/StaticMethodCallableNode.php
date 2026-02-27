<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\IdentifierNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\NameNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
use const PHP_VERSION_ID;
class StaticMethodCallableNode extends ExpressionNode
{
    public function __construct(public NameNode|ExpressionNode $class, public IdentifierNode|ExpressionNode $name, public ?Position $position = null)
    {
    }
    public function print(PrintContext $context): string
    {
        $name = match (\true) {
            $this->name instanceof VariableNode => $this->name->print($context),
            $this->name instanceof ExpressionNode => '{' . $this->name->print($context) . '}',
            default => $this->name,
        };
        return PHP_VERSION_ID < 80100 ? '[' . $this->class->print($context) . ', ' . $context->memberAsString($this->name) . ']' : $context->dereferenceExpr($this->class) . '::' . $name . '(...)';
    }
    public function &getIterator(): \Generator
    {
        yield $this->class;
        yield $this->name;
    }
}
class_alias(StaticMethodCallableNode::class, StaticCallableNode::class);
if (\false) {
    /** @deprecated use Latte\Compiler\Nodes\Php\Expression\StaticMethodCallableNode */
    class StaticCallableNode
    {
    }
}
