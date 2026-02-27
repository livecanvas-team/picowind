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

use PicowindDeps\Twig\Node\Expression\AbstractExpression;
use PicowindDeps\Twig\Node\Expression\Unary\NotUnary;
use PicowindDeps\Twig\Parser;
use PicowindDeps\Twig\Token;
/**
 * @internal
 */
final class IsNotExpressionParser extends IsExpressionParser
{
    public function parse(Parser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        return new NotUnary(parent::parse($parser, $expr, $token), $token->getLine());
    }
    public function getName(): string
    {
        return 'is not';
    }
}
