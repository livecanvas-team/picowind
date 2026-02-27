<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PicowindDeps\Symfony\Component\Cache\Exception;

use PicowindDeps\Psr\Cache\CacheException as Psr6CacheInterface;
use PicowindDeps\Psr\SimpleCache\CacheException as SimpleCacheInterface;
if (interface_exists(SimpleCacheInterface::class)) {
    class LogicException extends \LogicException implements Psr6CacheInterface, SimpleCacheInterface
    {
    }
} else {
    class LogicException extends \LogicException implements Psr6CacheInterface
    {
    }
}
