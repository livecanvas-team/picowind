<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render\Latte;

use Generator;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * Custom Latte tag for rendering Handlebars templates
 *
 * Syntax: {handlebars 'template.hbs'}
 *         {handlebars 'template.hbs', [var1 => value1]}
 */
class HandlebarsTag extends StatementNode
{
    public function __construct(
        public Node $template,
        public ?Node $params = null,
    ) {}

    public static function create(Tag $tag): self
    {
        $tag->outputMode = $tag::OutputRemoveIndentation;

        $template = $tag->parser->parseUnquotedStringOrExpression();
        $params = null;
        if ($tag->parser->stream->tryConsume(',')) {
            $params = $tag->parser->parseExpression();
        }

        return new self($template, $params);
    }

    public function print(PrintContext $context): string
    {
        $params = $this->params ? $this->params->print($context) : '[]';

        return $context->format(
            <<<'XX'
            echo \Picowind\render(%node, %raw, 'handlebars', false);
            XX,
            $this->template,
            $params,
        );
    }

    public function &getIterator(): Generator
    {
        yield $this->template;
        if ($this->params) {
            yield $this->params;
        }
    }
}
