<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PicowindDeps\Twig\Profiler\NodeVisitor;

use PicowindDeps\Twig\Environment;
use PicowindDeps\Twig\Node\BlockNode;
use PicowindDeps\Twig\Node\BodyNode;
use PicowindDeps\Twig\Node\MacroNode;
use PicowindDeps\Twig\Node\ModuleNode;
use PicowindDeps\Twig\Node\Node;
use PicowindDeps\Twig\Node\Nodes;
use PicowindDeps\Twig\NodeVisitor\NodeVisitorInterface;
use PicowindDeps\Twig\Profiler\Node\EnterProfileNode;
use PicowindDeps\Twig\Profiler\Node\LeaveProfileNode;
use PicowindDeps\Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class ProfilerNodeVisitor implements NodeVisitorInterface
{
    private $varName;
    public function __construct(private string $extensionName)
    {
        $this->varName = \sprintf('__internal_%s', hash(\PHP_VERSION_ID < 80100 ? 'sha256' : 'xxh128', $extensionName));
    }
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ModuleNode) {
            $node->setNode('display_start', new Nodes([new EnterProfileNode($this->extensionName, Profile::TEMPLATE, $node->getTemplateName(), $this->varName), $node->getNode('display_start')]));
            $node->setNode('display_end', new Nodes([new LeaveProfileNode($this->varName), $node->getNode('display_end')]));
        } elseif ($node instanceof BlockNode) {
            $node->setNode('body', new BodyNode([new EnterProfileNode($this->extensionName, Profile::BLOCK, $node->getAttribute('name'), $this->varName), $node->getNode('body'), new LeaveProfileNode($this->varName)]));
        } elseif ($node instanceof MacroNode) {
            $node->setNode('body', new BodyNode([new EnterProfileNode($this->extensionName, Profile::MACRO, $node->getAttribute('name'), $this->varName), $node->getNode('body'), new LeaveProfileNode($this->varName)]));
        }
        return $node;
    }
    public function getPriority(): int
    {
        return 0;
    }
}
