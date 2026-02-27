<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\Compiler\Nodes\Php;
use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\IdentifierNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Helpers;
class MethodCallNode extends ExpressionNode
{
    public function __construct(
        public ExpressionNode $object,
        public IdentifierNode|ExpressionNode $name,
        /** @var array<Php\ArgumentNode> */
        public array $args = [],
        public bool $nullsafe = \false,
        public ?Position $position = null
    )
    {
        (function (Php\ArgumentNode ...$args) {
        })(...$args);
    }
    public function print(PrintContext $context): string
    {
        return $context->dereferenceExpr($this->object) . ($this->nullsafe ? '?->' : '->') . $context->objectProperty($this->name) . '(' . $context->implode($this->args) . ')';
    }
    public function &getIterator(): \Generator
    {
        yield $this->object;
        yield $this->name;
        foreach ($this->args as &$item) {
            yield $item;
        }
        Helpers::removeNulls($this->args);
    }
}
