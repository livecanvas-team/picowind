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
 * {do expression}
 */
class DoNode extends StatementNode
{
    private const RefusedKeywords = ['for', 'foreach', 'switch', 'while', 'if', 'do', 'try', 'include', 'include_once', 'require', 'require_once', 'throw', 'yield', 'return', 'exit', 'break', 'continue', 'class', 'function', 'interface', 'trait', 'enum'];
    public ExpressionNode $expression;
    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $token = $tag->parser->stream->peek();
        if ($token->is(...self::RefusedKeywords)) {
            $tag->parser->throwReservedKeywordException($token);
        }
        $node = new static();
        $node->expression = $tag->parser->parseExpression();
        return $node;
    }
    public function print(PrintContext $context): string
    {
        return $context->format('%node %line;', $this->expression, $this->position);
    }
    public function &getIterator(): \Generator
    {
        yield $this->expression;
    }
}
