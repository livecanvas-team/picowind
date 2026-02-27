<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\CompileException;
use PicowindDeps\Latte\Compiler\Nodes\AreaNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\ListNode;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
/**
 * {iterateWhile $cond}
 */
class IterateWhileNode extends StatementNode
{
    public ExpressionNode $condition;
    public AreaNode $content;
    public ?ExpressionNode $key;
    public ExpressionNode|ListNode $value;
    public bool $postTest;
    /** @return \Generator<int, ?array, array{AreaNode, ?Tag}, static> */
    public static function create(Tag $tag): \Generator
    {
        $foreach = $tag->closestTag([ForeachNode::class])?->node;
        if (!$foreach) {
            throw new CompileException("Tag {{$tag->name}} must be inside {foreach} ... {/foreach}.", $tag->position);
        }
        $node = $tag->node = new static();
        $node->postTest = $tag->parser->isEnd();
        if (!$node->postTest) {
            $node->condition = $tag->parser->parseExpression();
        }
        $node->key = $foreach->key;
        $node->value = $foreach->value;
        [$node->content, $nextTag] = yield;
        if ($node->postTest) {
            $nextTag->expectArguments();
            $node->condition = $nextTag->parser->parseExpression();
        }
        return $node;
    }
    public function print(PrintContext $context): string
    {
        $stmt = $context->format(<<<'XX'
if (!$iterator->hasNext() || !(%node)) {
	break;
}
$iterator->next();
[%node, %node] = [$iterator->key(), $iterator->current()];
XX
, $this->condition, $this->key, $this->value);
        $stmt = $this->postTest ? $this->content->print($context) . "\n" . $stmt : $stmt . "\n" . $this->content->print($context);
        return $context->format(<<<'XX'
do %line {
	%raw
} while (true);

XX
, $this->position, $stmt);
    }
    public function &getIterator(): \Generator
    {
        yield $this->condition;
        yield $this->content;
    }
}
