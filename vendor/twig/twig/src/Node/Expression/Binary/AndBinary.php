<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PicowindDeps\Twig\Node\Expression\Binary;

use PicowindDeps\Twig\Compiler;
use PicowindDeps\Twig\Node\Expression\ReturnBoolInterface;
class AndBinary extends AbstractBinary implements ReturnBoolInterface
{
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('&&');
    }
}
