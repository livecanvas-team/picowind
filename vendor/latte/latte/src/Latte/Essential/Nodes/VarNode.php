<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Essential\Nodes;

use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\AssignNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\AssignOpNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\AuxiliaryNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\TernaryNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\VariableNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Scalar\NullNode;
use PicowindDeps\Latte\Compiler\Nodes\StatementNode;
use PicowindDeps\Latte\Compiler\PrintContext;
use PicowindDeps\Latte\Compiler\Tag;
use PicowindDeps\Latte\Compiler\Token;
use PicowindDeps\Latte\Helpers;
use function assert, implode;
/**
 * {var [type] $var = value, ...}
 * {default [type] $var = value, ...}
 */
class VarNode extends StatementNode
{
    public bool $default;
    /** @var AssignNode[] */
    public array $assignments = [];
    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new static();
        $node->default = $tag->name === 'default';
        $node->assignments = self::parseAssignments($tag, $node->default);
        return $node;
    }
    private static function parseAssignments(Tag $tag, bool $default): array
    {
        $stream = $tag->parser->stream;
        $res = [];
        do {
            $tag->parser->parseType();
            $save = $stream->getIndex();
            $expr = $stream->is(Token::Php_Variable) ? $tag->parser->parseExpression() : null;
            if ($expr instanceof VariableNode) {
                $res[] = new AssignNode($expr, new NullNode());
            } elseif ($expr instanceof AssignNode && (!$default || $expr->var instanceof VariableNode)) {
                $res[] = $expr;
            } else {
                $stream->seek($save);
                $stream->throwUnexpectedException(addendum: ' in ' . $tag->getNotation());
            }
        } while ($stream->tryConsume(',') && !$stream->peek()->isEnd());
        return $res;
    }
    public function print(PrintContext $context): string
    {
        $res = [];
        foreach ($this->assignments as $assign) {
            if ($this->default) {
                assert($assign->var instanceof VariableNode);
                $assign = new AssignOpNode($assign->var, '??', new TernaryNode(new AuxiliaryNode(fn() => 'array_key_exists(' . $context->encodeString($assign->var->name) . ', get_defined_vars())'), new NullNode(), $assign->expr));
            }
            $res[] = $assign->print($context);
        }
        return $context->format('%raw %line;', implode('; ', $res), $this->position);
    }
    public function &getIterator(): \Generator
    {
        foreach ($this->assignments as &$assign) {
            yield $assign;
        }
        Helpers::removeNulls($this->assignments);
    }
}
