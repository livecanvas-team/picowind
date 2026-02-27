<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
/**
 * {import "file"[, args]}
 */
class ImportNode extends StatementNode
{
    public ExpressionNode $file;
    public ArrayNode $args;
    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new static();
        $node->file = $tag->parser->parseUnquotedStringOrExpression();
        $tag->parser->stream->tryConsume(',');
        $node->args = $tag->parser->parseArguments();
        return $node;
    }
    public function print(PrintContext $context): string
    {
        return $context->format('$this->createTemplate(%raw, %node? + $this->params, "import")->render() %line;', $context->ensureString($this->file, 'Template name'), $this->args, $this->position);
    }
    public function &getIterator(): \Generator
    {
        yield $this->file;
        yield $this->args;
    }
}
