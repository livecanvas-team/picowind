<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\CompileException;
use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Scalar\BooleanNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Scalar\NullNode;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
/**
 * {extends none | auto | "file"}
 * {layout none | auto | "file"}
 */
class ExtendsNode extends StatementNode
{
    public ExpressionNode $extends;
    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new static();
        if (!$tag->isInHead()) {
            throw new CompileException("{{$tag->name}} must be placed in template head.", $tag->position);
        } elseif ($tag->parser->stream->tryConsume('auto')) {
            $node->extends = new NullNode();
        } elseif ($tag->parser->stream->tryConsume('none')) {
            $node->extends = new BooleanNode(\false);
        } else {
            $node->extends = $tag->parser->parseUnquotedStringOrExpression();
        }
        return $node;
    }
    public function print(PrintContext $context): string
    {
        return $context->format('$this->parentName = %node;', $this->extends);
    }
    public function &getIterator(): \Generator
    {
        yield $this->extends;
    }
}
