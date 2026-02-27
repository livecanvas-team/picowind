<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PicowindDeps\Twig\ExpressionParser\Infix;

use PicowindDeps\Twig\ExpressionParser\AbstractExpressionParser;
use PicowindDeps\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use PicowindDeps\Twig\ExpressionParser\InfixAssociativity;
use PicowindDeps\Twig\ExpressionParser\InfixExpressionParserInterface;
use PicowindDeps\Twig\Node\Expression\AbstractExpression;
use PicowindDeps\Twig\Node\Expression\ArrowFunctionExpression;
use PicowindDeps\Twig\Parser;
use PicowindDeps\Twig\Token;
/**
 * @internal
 */
final class ArrowExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function parse(Parser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        // As the expression of the arrow function is independent from the current precedence, we want a precedence of 0
        return new ArrowFunctionExpression($parser->parseExpression(), $expr, $token->getLine());
    }
    public function getName(): string
    {
        return '=>';
    }
    public function getDescription(): string
    {
        return 'Arrow function (x => expr)';
    }
    public function getPrecedence(): int
    {
        return 250;
    }
    public function getAssociativity(): InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
