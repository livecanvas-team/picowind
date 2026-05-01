<?php

namespace PicowindDeps\DevTheorem\HandlebarsParser\Ast;

readonly class PathSegment
{
    public function __construct(public string $part, public string $original, public ?string $separator)
    {
    }
}
