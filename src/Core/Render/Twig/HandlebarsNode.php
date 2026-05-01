<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render\Twig;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Represents a {% handlebars %} node in Twig templates
 */
class HandlebarsNode extends Node
{
    public function __construct(
        Node $template,
        ?Node $with = null,
        bool $only = false,
        int $lineno = 0,
        ?string $tag = null,
    ) {
        $nodes = ['template' => $template];
        if (null !== $with) {
            $nodes['with'] = $with;
        }

        parent::__construct($nodes, ['only' => $only], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);
        $compiler->write('echo $this->env->getFunction(\'handlebars\')->getCallable()(');
        $compiler->raw('$context, ');
        $compiler->subcompile($this->getNode('template'));

        if ($this->hasNode('with')) {
            $compiler->raw(', ');
            $compiler->subcompile($this->getNode('with'));
        } else {
            $compiler->raw(', []');
        }

        $compiler->raw(', ');
        $compiler->raw($this->getAttribute('only') ? 'true' : 'false');
        $compiler->raw(");\n");
    }
}
