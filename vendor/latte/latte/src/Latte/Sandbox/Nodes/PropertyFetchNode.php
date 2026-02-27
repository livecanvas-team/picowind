<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace PicowindDeps\Latte\Sandbox\Nodes;

use PicowindDeps\Latte\Compiler\Nodes\Php\Expression;
use PicowindDeps\Latte\Compiler\PrintContext;
class PropertyFetchNode extends Expression\PropertyFetchNode
{
    public function __construct(Expression\PropertyFetchNode $from)
    {
        parent::__construct($from->object, $from->name, $from->nullsafe, $from->position);
    }
    public function print(PrintContext $context): string
    {
        return '$this->global->sandbox->prop(' . $this->object->print($context) . ', ' . $context->memberAsString($this->name) . ')' . ($this->nullsafe ? '?->' : '->') . $context->objectProperty($this->name);
    }
}
