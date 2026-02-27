<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
/**
 * {debugbreak [$cond]}
 */
class DebugbreakNode extends StatementNode
{
    public ?ExpressionNode $condition;
    public static function create(Tag $tag): static
    {
        $node = new static();
        $node->condition = $tag->parser->isEnd() ? null : $tag->parser->parseExpression();
        return $node;
    }
    public function print(PrintContext $context): string
    {
        if (function_exists($func = 'debugbreak') || function_exists($func = 'xdebug_break')) {
            return $context->format(($this->condition ? 'if (%1.node) ' : '') . $func . '() %0.line;', $this->position, $this->condition);
        }
        return '';
    }
    public function &getIterator(): \Generator
    {
        if ($this->condition) {
            yield $this->condition;
        }
    }
}
