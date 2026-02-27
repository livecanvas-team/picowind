<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Expression;

use PicowindDeps\Latte\Compiler\Nodes\Php;
use PicowindDeps\Latte\Compiler\Nodes\Php\MatchArmNode;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Helpers;
class MatchNode extends Php\ExpressionNode
{
    public function __construct(
        public Php\ExpressionNode $cond,
        /** @var MatchArmNode[] */
        public array $arms = [],
        public ?Position $position = null
    )
    {
        (function (MatchArmNode ...$args) {
        })(...$arms);
    }
    public function print(PrintContext $context): string
    {
        $res = 'match (' . $this->cond->print($context) . ') {';
        foreach ($this->arms as $node) {
            $res .= "\n" . $node->print($context) . ',';
        }
        $res .= "\n}";
        return $res;
    }
    public function &getIterator(): \Generator
    {
        yield $this->cond;
        foreach ($this->arms as &$item) {
            yield $item;
        }
        Helpers::removeNulls($this->arms);
    }
}
