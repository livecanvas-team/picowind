<?php

namespace PicowindDeps\Illuminate\Contracts\Container;

use Exception;
use PicowindDeps\Psr\Container\ContainerExceptionInterface;
class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    //
}
