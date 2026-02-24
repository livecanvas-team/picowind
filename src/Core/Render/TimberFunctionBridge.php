<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use InvalidArgumentException;
use Timber\Twig as TimberTwig;

class TimberFunctionBridge
{
    /**
     * @var array<string, callable>
     */
    private array $functions = [];

    /**
     * @param ?array<string, mixed> $functions
     */
    public function __construct(?array $functions = null)
    {
        $functions ??= (new TimberTwig())->get_timber_functions();

        $filtered = call_user_func('apply_filters', 'f!picowind/render/timber:functions', $functions);
        if (is_array($filtered)) {
            $functions = $filtered;
        }

        foreach ($functions as $name => $definition) {
            if (! is_string($name) || '' === $name) {
                continue;
            }

            $callable = $definition;
            if (is_array($definition) && array_key_exists('callable', $definition)) {
                $callable = $definition['callable'];
            }

            if (! is_callable($callable)) {
                continue;
            }

            $this->functions[$name] = $callable;
        }
    }

    /**
     * @return array<string, callable>
     */
    public function all(): array
    {
        return $this->functions;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->functions);
    }

    public function call(string $name, mixed ...$args): mixed
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(sprintf('Unknown Timber function "%s".', $name));
        }

        return ($this->functions[$name])(...$args);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->call($name, ...$arguments);
    }
}
