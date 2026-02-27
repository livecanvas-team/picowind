<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Compiler\Nodes\Php\Scalar;

use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\InterpolatedStringPartNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\ScalarNode;
use PicowindDeps\Latte\Compiler\PhpHelpers;
use PicowindDeps\Latte\Compiler\Position;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Helpers;
use function substr;
class InterpolatedStringNode extends ScalarNode
{
    public function __construct(
        /** @var array<ExpressionNode|InterpolatedStringPartNode> */
        public array $parts,
        public ?Position $position = null
    )
    {
    }
    /** @param array<ExpressionNode|InterpolatedStringPartNode> $parts */
    public static function parse(array $parts, Position $position): static
    {
        foreach ($parts as $part) {
            if ($part instanceof InterpolatedStringPartNode) {
                $part->value = PhpHelpers::decodeEscapeSequences($part->value, '"');
            }
        }
        return new static($parts, $position);
    }
    public function print(PrintContext $context): string
    {
        $s = '';
        $expr = \false;
        foreach ($this->parts as $part) {
            if ($part instanceof InterpolatedStringPartNode) {
                $s .= substr($context->encodeString($part->value, '"'), 1, -1);
                continue;
            }
            $partStr = $part->print($context);
            if ($partStr[0] === '$' && $part->isVariable()) {
                $s .= '{' . $partStr . '}';
            } else {
                $s .= '" . (' . $partStr . ') . "';
                $expr = \true;
            }
        }
        return $expr ? '("' . $s . '")' : '"' . $s . '"';
    }
    public function &getIterator(): \Generator
    {
        foreach ($this->parts as &$item) {
            yield $item;
        }
        Helpers::removeNulls($this->parts);
    }
}
