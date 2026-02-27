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
/**
 * {templateType ClassName}
 */
class TemplateTypeNode extends StatementNode
{
    public static function create(Tag $tag): static
    {
        if (!$tag->isInHead()) {
            throw new CompileException('{templateType} is allowed only in template header.', $tag->position);
        }
        $tag->expectArguments('class name');
        $tag->parser->parseExpression();
        return new static();
    }
    public function print(PrintContext $context): string
    {
        return '';
    }
    public function &getIterator(): \Generator
    {
        \false && yield;
    }
}
