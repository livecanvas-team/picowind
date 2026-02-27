<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler;

use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\ParameterNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Scalar;
/** @internal */
final class Block
{
    public string $method;
    public string $content;
    public string $escaping;
    /** @var ParameterNode[] */
    public array $parameters = [];
    public function __construct(public ExpressionNode $name, public int|string $layer, public Tag $tag)
    {
    }
    public function isDynamic(): bool
    {
        return !$this->name instanceof Scalar\StringNode && !$this->name instanceof Scalar\IntegerNode;
    }
}
