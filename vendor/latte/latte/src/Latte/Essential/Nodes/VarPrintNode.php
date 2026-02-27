<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
/**
 * {varPrint [all]}
 */
class VarPrintNode extends StatementNode
{
    public bool $all;
    public static function create(Tag $tag): static
    {
        $stream = $tag->parser->stream;
        $node = new static();
        $node->all = $stream->consume()->text === 'all';
        return $node;
    }
    public function print(PrintContext $context): string
    {
        $vars = $this->all ? 'get_defined_vars()' : 'array_diff_key(get_defined_vars(), $this->getParameters())';
        return <<<XX
\$ʟ_bp = new PicowindDeps\\Latte\\Essential\\Blueprint;
\$ʟ_bp->printBegin();
\$ʟ_bp->printVars({$vars});
\$ʟ_bp->printEnd();
exit;
XX;
    }
    public function &getIterator(): \Generator
    {
        \false && yield;
    }
}
