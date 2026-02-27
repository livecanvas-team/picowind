<?php

declare (strict_types=1);
/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */
namespace Picowind\Core\Render\Latte;

use PicowindDeps\Latte\CompileException;
use PicowindDeps\Latte\Compiler\Nodes\PrintNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\Expression\FunctionCallNode;
use PicowindDeps\Latte\Compiler\Nodes\Php\NameNode;
use PicowindDeps\Latte\Compiler\Tag;
class UnderscoreFunctionTag
{
    public static function create(Tag $tag): PrintNode
    {
        $tag->outputMode = $tag::OutputKeepIndentation;
        $tag->expectArguments();
        $node = new PrintNode();
        $node->expression = $tag->parser->parseExpression();
        if (!$node->expression instanceof FunctionCallNode || !$node->expression->name instanceof NameNode) {
            throw new CompileException('Tag {_...} expects function call syntax like {_n(...)}.', $tag->position);
        }
        $name = (string) $node->expression->name;
        if (!str_starts_with($name, '_')) {
            $node->expression->name = new NameNode('_' . $name, position: $node->expression->name->position);
        }
        $node->modifier = $tag->parser->parseModifier();
        $node->modifier->escape = \true;
        return $node;
    }
}
