<?php

namespace PicowindDeps\DevTheorem\HandlebarsParser\Ast;

class UndefinedLiteral extends Literal
{
    public function __construct(public null $value, null $original, SourceLocation $loc)
    {
        parent::__construct($original, 'UndefinedLiteral', $loc);
    }
}
