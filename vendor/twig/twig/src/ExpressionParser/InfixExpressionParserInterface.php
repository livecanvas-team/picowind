<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PicowindDeps\Twig\ExpressionParser;

use PicowindDeps\Twig\Error\SyntaxError;
use PicowindDeps\Twig\Node\Expression\AbstractExpression;
use PicowindDeps\Twig\Parser;
use PicowindDeps\Twig\Token;
interface InfixExpressionParserInterface extends ExpressionParserInterface
{
    /**
     * @throws SyntaxError
     */
    public function parse(Parser $parser, AbstractExpression $left, Token $token): AbstractExpression;
    public function getAssociativity(): InfixAssociativity;
}
