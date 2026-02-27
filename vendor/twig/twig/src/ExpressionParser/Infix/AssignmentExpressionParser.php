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

use PicowindDeps\Twig\Error\SyntaxError;
use PicowindDeps\Twig\ExpressionParser\InfixAssociativity;
use PicowindDeps\Twig\Node\Expression\AbstractExpression;
use PicowindDeps\Twig\Node\Expression\ArrayExpression;
use PicowindDeps\Twig\Node\Expression\Binary\AbstractBinary;
use PicowindDeps\Twig\Node\Expression\Binary\ObjectDestructuringSetBinary;
use PicowindDeps\Twig\Node\Expression\Binary\SequenceDestructuringSetBinary;
use PicowindDeps\Twig\Node\Expression\Binary\SetBinary;
use PicowindDeps\Twig\Node\Expression\Variable\ContextVariable;
use PicowindDeps\Twig\Parser;
use PicowindDeps\Twig\Token;
/**
 * @internal
 */
class AssignmentExpressionParser extends BinaryOperatorExpressionParser
{
    public function __construct(string $name)
    {
        parent::__construct(SetBinary::class, $name, 0, InfixAssociativity::Right);
    }
    /**
     * @return AbstractBinary
     */
    public function parse(Parser $parser, AbstractExpression $left, Token $token): AbstractExpression
    {
        if (!$left instanceof ContextVariable && !$left instanceof ArrayExpression) {
            throw new SyntaxError(\sprintf('Cannot assign to "%s", only variables can be assigned.', $left::class), $token->getLine(), $parser->getStream()->getSourceContext());
        }
        $right = $parser->parseExpression(InfixAssociativity::Left === $this->getAssociativity() ? $this->getPrecedence() + 1 : $this->getPrecedence());
        $right = match ($this->getName()) {
            '=' => $right,
            default => throw new \LogicException(\sprintf('Unknown operator: %s.', $this->getName())),
        };
        if ($left instanceof ArrayExpression) {
            if ($left->isSequence()) {
                return new SequenceDestructuringSetBinary($left, $right, $token->getLine());
            } else {
                return new ObjectDestructuringSetBinary($left, $right, $token->getLine());
            }
        } else {
            return new SetBinary($left, $right, $token->getLine());
        }
    }
    public function getDescription(): string
    {
        return 'Assignment operator';
    }
}
