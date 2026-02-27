<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PicowindDeps\Twig\Node\Expression\Unary;

use PicowindDeps\Twig\Node\Expression\AbstractExpression;
/**
 * @internal
 */
interface UnaryInterface
{
    public function __construct(AbstractExpression $node, int $lineno);
}
