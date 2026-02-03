<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Exceptions;

use Exception;

class UnsupportedRenderEngineException extends Exception
{
    public function __construct(?string $engine, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $engineString = $engine ?? 'null';
        $fullMessage = 'Unsupported render engine: ' . $engineString;
        if ('' !== $message && '0' !== $message) {
            $fullMessage .= '. ' . $message;
        }

        parent::__construct($fullMessage, $code, $previous);
    }
}
