<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\CompileException;
use PicowindDeps\Latte\Compiler\Nodes\FragmentNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\ExpressionNode;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\Nodes\TextNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
/**
 * {switch} ... {case} ... {default}
 */
class SwitchNode extends StatementNode
{
    public ?ExpressionNode $expression;
    /** @var array<array{?ArrayNode, int, FragmentNode}> */
    public array $cases = [];
    /** @return \Generator<int, ?array, array{FragmentNode, Tag}, static> */
    public static function create(Tag $tag): \Generator
    {
        if ($tag->isNAttribute()) {
            throw new CompileException('Attribute n:switch is not supported.', $tag->position);
        }
        $node = $tag->node = new static();
        $node->expression = $tag->parser->isEnd() ? null : $tag->parser->parseExpression();
        [$content, $nextTag] = yield ['case', 'default'];
        foreach ($content->children as $child) {
            if (!$child instanceof TextNode || !$child->isWhitespace()) {
                throw new CompileException('No content is allowed between {switch} and {case}', $child->position);
            }
        }
        $default = 0;
        while (\true) {
            if ($nextTag->name === 'case') {
                $nextTag->expectArguments();
                [$case, $line] = [$nextTag->parser->parseArguments(), $nextTag->position];
                [$content, $nextTag] = yield ['case', 'default'];
                $node->cases[] = [$case, $line, $content];
            } elseif ($nextTag->name === 'default') {
                if ($default++) {
                    throw new CompileException('Tag {switch} may only contain one {default} clause.', $nextTag->position);
                }
                $line = $nextTag->position;
                [$content, $nextTag] = yield ['case', 'default'];
                $node->cases[] = [null, $line, $content];
            } else {
                return $node;
            }
        }
    }
    public function print(PrintContext $context): string
    {
        $res = $context->format('$ʟ_switch = (%node) %line;', $this->expression, $this->position);
        $first = \true;
        $default = null;
        foreach ($this->cases as [$case, $line, $content]) {
            if (!$case) {
                $default = $content->print($context);
                continue;
            } elseif (!$first) {
                $res .= 'else';
            }
            $first = \false;
            $res .= $context->format('if (in_array($ʟ_switch, %node, true)) %line { %node } ', $case, $line, $content);
        }
        if ($default) {
            $res .= $first ? $default : 'else { ' . $default . ' } ';
        }
        return $res;
    }
    public function &getIterator(): \Generator
    {
        yield $this->expression;
        foreach ($this->cases as [&$case, , &$stmt]) {
            if ($case) {
                yield $case;
            }
            yield $stmt;
        }
    }
}
