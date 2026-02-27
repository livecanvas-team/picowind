<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\CompileException;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
use function preg_match;
/**
 * n:tag="..."
 */
final class NTagNode extends StatementNode
{
    public static function create(Tag $tag): void
    {
        if (preg_match('(style$|script$)iA', $tag->htmlElement->name)) {
            throw new CompileException('Attribute n:tag is not allowed in <script> or <style>', $tag->position);
        }
        $tag->expectArguments();
        $tag->htmlElement->variableName = $tag->parser->parseExpression();
    }
    public function print(PrintContext $context): string
    {
        throw new \LogicException('Cannot directly print');
    }
    public function &getIterator(): \Generator
    {
        \false && yield;
    }
}
