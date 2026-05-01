<?php

namespace PicowindDeps\DevTheorem\HandlebarsParser;

class ParserFactory
{
    public function create(): Parser
    {
        return new Parser(new Lexer());
    }
}
