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
 * {rollback}
 */
class RollbackNode extends StatementNode
{
    public static function create(Tag $tag): static
    {
        if (!$tag->closestTag([TryNode::class])) {
            throw new CompileException('Tag {rollback} must be inside {try} ... {/try}.', $tag->position);
        }
        return new static();
    }
    public function print(PrintContext $context): string
    {
        return 'throw new Latte\Essential\RollbackException;';
    }
    public function &getIterator(): \Generator
    {
        \false && yield;
    }
}
