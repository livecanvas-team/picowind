<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Exceptions;

use Exception;

class TemplateNotExistException extends Exception
{
    public function __construct(string|array $path, int $code = 0, ?\Throwable $previous = null)
    {
        $pathString = is_array($path) ? implode(', ', $path) : $path;
        parent::__construct('Template file does not exist: ' . $pathString, $code, $previous);
    }
}
