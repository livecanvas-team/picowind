<?php

declare (strict_types=1);
/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */
namespace Picowind\Core\Render\Twig;

use PicowindDeps\Twig\Token;
use PicowindDeps\Twig\TokenParser\AbstractTokenParser;
/**
 * Parses {% handlebars %} tags in Twig templates
 *
 * Syntax:
 *   {% handlebars 'template.hbs' %}
 *   {% handlebars 'template.hbs' with {'var': 'value'} %}
 *   {% handlebars 'template.hbs' with {'var': 'value'} only %}
 */
class HandlebarsTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): \Picowind\Core\Render\Twig\HandlebarsNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $template = $this->parser->parseExpression();
        $with = null;
        $only = \false;
        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $with = $this->parser->parseExpression();
        }
        if ($stream->nextIf(Token::NAME_TYPE, 'only')) {
            $only = \true;
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        return new \Picowind\Core\Render\Twig\HandlebarsNode($template, $with, $only, $lineno, $this->getTag());
    }
    public function getTag(): string
    {
        return 'handlebars';
    }
}
