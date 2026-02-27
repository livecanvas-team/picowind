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
 * {dump [$var]}
 */
class DumpNode extends StatementNode
{
    public ?ExpressionNode $expression = null;
    public static function create(Tag $tag): static
    {
        $node = new static();
        $node->expression = $tag->parser->isEnd() ? null : $tag->parser->parseExpression();
        return $node;
    }
    public function print(PrintContext $context): string
    {
        return $this->expression ? $context->format('Tracy\Debugger::barDump(%node, %dump) %line;', $this->expression, $this->expression->print($context), $this->position) : $context->format("Tracy\\Debugger::barDump(get_defined_vars(), 'variables') %line;", $this->position);
    }
    public function &getIterator(): \Generator
    {
        if ($this->expression) {
            yield $this->expression;
        }
    }
}
