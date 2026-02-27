<?php

namespace PicowindDeps\Jenssegers\Blade;

use PicowindDeps\Illuminate\Container\Container;
class Application extends Container
{
    public function getNamespace()
    {
        return 'app\\';
    }
}
