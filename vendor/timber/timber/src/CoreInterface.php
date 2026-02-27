<?php

namespace PicowindDeps\Timber;

/**
 * Interface CoreInterface
 */
interface CoreInterface
{
    public function __call($field, $args);
    public function __get($field);
    /**
     * @return boolean
     */
    public function __isset($field);
}
