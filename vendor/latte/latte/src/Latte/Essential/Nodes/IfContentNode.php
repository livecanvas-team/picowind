<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\CompileException;
use PicowindDeps\Latte\Compiler\Nodes\AreaNode;
use PicowindDeps\Latte\Compiler\Nodes\AuxiliaryNode;
use PicowindDeps\Latte\Compiler\Nodes\Html\ElementNode;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
use PicowindDeps\Latte\Compiler\TemplateParser;
/**
 * n:ifcontent
 */
class IfContentNode extends StatementNode
{
    public AreaNode $content;
    public int $id;
    public ElementNode $htmlElement;
    public ?AreaNode $else = null;
    /** @return \Generator<int, ?array, array{AreaNode, ?Tag}, static> */
    public static function create(Tag $tag, TemplateParser $parser): \Generator
    {
        $node = $tag->node = new static();
        $node->id = $parser->generateId();
        [$node->content] = yield;
        $node->htmlElement = $tag->htmlElement;
        if (!$node->htmlElement->content) {
            throw new CompileException("Unnecessary n:ifcontent on empty element <{$node->htmlElement->name}>", $tag->position);
        }
        return $node;
    }
    public function print(PrintContext $context): string
    {
        try {
            $saved = $this->htmlElement->content;
            $else = $this->else ?? new AuxiliaryNode(fn() => '');
            $this->htmlElement->content = new AuxiliaryNode(fn() => <<<XX
ob_start();
try {
\t{$saved->print($context)}
} finally {
\t\$ʟ_ifc[{$this->id}] = rtrim(ob_get_flush()) === '';
}

XX
);
            return <<<XX
ob_start(fn() => '');
try {
\t{$this->content->print($context)}
} finally {
\tif (\$ʟ_ifc[{$this->id}] ?? null) {
\t\tob_end_clean();
\t\t{$else->print($context)}
\t} else {
\t\techo ob_get_clean();
\t}
}

XX;
        } finally {
            $this->htmlElement->content = $saved;
        }
    }
    public function &getIterator(): \Generator
    {
        yield $this->content;
    }
}
