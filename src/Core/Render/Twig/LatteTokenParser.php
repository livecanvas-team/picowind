<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render\Twig;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Parses {% latte %} tags in Twig templates
 *
 * Syntax:
 *   {% latte 'template.latte' %}
 *   {% latte 'template.latte' with {'var': 'value'} %}
 *   {% latte 'template.latte' with {'var': 'value'} only %}
 */
class LatteTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): LatteNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        // Parse template name (required)
        $template = $this->parser->parseExpression();

        // Initialize variables
        $with = null;
        $only = false;

        // Check for 'with' keyword
        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $with = $this->parser->parseExpression();
        }

        // Check for 'only' keyword
        if ($stream->nextIf(Token::NAME_TYPE, 'only')) {
            $only = true;
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new LatteNode($template, $with, $only, $lineno, $this->getTag());
    }

    public function getTag(): string
    {
        return 'latte';
    }
}
