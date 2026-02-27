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

use PicowindDeps\Twig\Attribute\FirstClassTwigCallableReady;
use PicowindDeps\Twig\Error\SyntaxError;
use PicowindDeps\Twig\ExpressionParser\AbstractExpressionParser;
use PicowindDeps\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use PicowindDeps\Twig\ExpressionParser\InfixAssociativity;
use PicowindDeps\Twig\ExpressionParser\InfixExpressionParserInterface;
use PicowindDeps\Twig\Node\EmptyNode;
use PicowindDeps\Twig\Node\Expression\AbstractExpression;
use PicowindDeps\Twig\Node\Expression\MacroReferenceExpression;
use PicowindDeps\Twig\Node\Expression\NameExpression;
use PicowindDeps\Twig\Parser;
use PicowindDeps\Twig\Token;
/**
 * @internal
 */
final class FunctionExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    use ArgumentsTrait;
    private $readyNodes = [];
    public function parse(Parser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $line = $token->getLine();
        if (!$expr instanceof NameExpression) {
            throw new SyntaxError('Function name must be an identifier.', $line, $parser->getStream()->getSourceContext());
        }
        $name = $expr->getAttribute('name');
        if (null !== $alias = $parser->getImportedSymbol('function', $name)) {
            return new MacroReferenceExpression($alias['node']->getNode('var'), $alias['name'], $this->parseCallableArguments($parser, $line, \false), $line);
        }
        $args = $this->parseNamedArguments($parser, \false);
        $function = $parser->getFunction($name, $line);
        if ($function->getParserCallable()) {
            $fakeNode = new EmptyNode($line);
            $fakeNode->setSourceContext($parser->getStream()->getSourceContext());
            return $function->getParserCallable()($parser, $fakeNode, $args, $line);
        }
        if (!isset($this->readyNodes[$class = $function->getNodeClass()])) {
            $this->readyNodes[$class] = (bool) (new \ReflectionClass($class))->getConstructor()->getAttributes(FirstClassTwigCallableReady::class);
        }
        if (!$ready = $this->readyNodes[$class]) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigFunction" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }
        return new $class($ready ? $function : $function->getName(), $args, $line);
    }
    public function getName(): string
    {
        return '(';
    }
    public function getDescription(): string
    {
        return 'Twig function call';
    }
    public function getPrecedence(): int
    {
        return 512;
    }
    public function getAssociativity(): InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
