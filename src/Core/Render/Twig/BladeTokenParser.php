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
 * Parses {% blade %} tags in Twig templates
 *
 * Syntax:
 *   {% blade 'template.blade.php' %}
 *   {% blade 'template.blade.php' with {'var': 'value'} %}
 *   {% blade 'template.blade.php' with {'var': 'value'} only %}
 */
class BladeTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): BladeNode
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

        return new BladeNode($template, $with, $only, $lineno, $this->getTag());
    }

    public function getTag(): string
    {
        return 'blade';
    }
}
