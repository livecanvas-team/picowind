<?php

namespace PicowindDeps\DevTheorem\HandlebarsParser\Ast;

readonly class CloseBlock
{
    public function __construct(public PathExpression|Literal $path, public StripFlags $strip)
    {
    }
}
