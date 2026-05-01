<?php

declare (strict_types=1);
/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */
namespace Picowind\Core\Render;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use ReflectionMethod;
use ReflectionProperty;
use Stringable;
use Throwable;
use Traversable;
/**
 * Makes object-based WordPress/Timber data readable by php-handlebars' array-style runtime.
 *
 * @implements ArrayAccess<string|int, mixed>
 * @implements IteratorAggregate<string|int, mixed>
 */
final class HandlebarsContextValue implements ArrayAccess, Countable, IteratorAggregate, Stringable
{
    /**
     * @param Closure(mixed): mixed $normalize
     */
    public function __construct(private readonly object $value, private readonly Closure $normalize)
    {
    }
    public function value(): object
    {
        return $this->value;
    }
    public function offsetExists(mixed $offset): bool
    {
        $exists = \false;
        $this->read($offset, $exists);
        return $exists;
    }
    public function offsetGet(mixed $offset): mixed
    {
        $exists = \false;
        $value = $this->read($offset, $exists);
        return $exists ? ($this->normalize)($value) : null;
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Template contexts are read-only.
    }
    public function offsetUnset(mixed $offset): void
    {
        // Template contexts are read-only.
    }
    public function count(): int
    {
        if ($this->value instanceof Countable) {
            return count($this->value);
        }
        if ($this->value instanceof Traversable) {
            return iterator_count($this->value);
        }
        return count(get_object_vars($this->value));
    }
    public function getIterator(): Traversable
    {
        if ($this->value instanceof Traversable) {
            foreach ($this->value as $key => $value) {
                yield $key => ($this->normalize)($value);
            }
            return;
        }
        yield from new ArrayIterator(array_map($this->normalize, get_object_vars($this->value)));
    }
    public function __toString(): string
    {
        if ($this->value instanceof Stringable) {
            return (string) $this->value;
        }
        return '[object Object]';
    }
    private function read(mixed $offset, bool &$exists): mixed
    {
        if (!is_string($offset) && !is_int($offset)) {
            $exists = \false;
            return null;
        }
        if ($this->value instanceof ArrayAccess && $this->value->offsetExists($offset)) {
            $exists = \true;
            return $this->value->offsetGet($offset);
        }
        $key = (string) $offset;
        $property = $this->read_property($key, $exists);
        if ($exists) {
            return $property;
        }
        return $this->read_method($key, $exists);
    }
    private function read_property(string $key, bool &$exists): mixed
    {
        if (isset($this->value->{$key})) {
            $exists = \true;
            return $this->value->{$key};
        }
        if (property_exists($this->value, $key)) {
            $property = new ReflectionProperty($this->value, $key);
            if ($property->isPublic()) {
                $exists = \true;
                return $this->value->{$key};
            }
        }
        if (method_exists($this->value, '__get')) {
            try {
                $value = $this->value->{$key};
            } catch (Throwable) {
                $exists = \false;
                return null;
            }
            if (null !== $value) {
                $exists = \true;
                return $value;
            }
        }
        $exists = \false;
        return null;
    }
    private function read_method(string $key, bool &$exists): mixed
    {
        foreach ($this->method_candidates($key) as $method) {
            if (!method_exists($this->value, $method)) {
                continue;
            }
            $reflectionMethod = new ReflectionMethod($this->value, $method);
            if (!$reflectionMethod->isPublic() || 0 !== $reflectionMethod->getNumberOfRequiredParameters()) {
                continue;
            }
            $exists = \true;
            return $this->value->{$method}();
        }
        $exists = \false;
        return null;
    }
    /**
     * @return array<int, string>
     */
    private function method_candidates(string $key): array
    {
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));
        return array_values(array_unique([$key, 'get' . $studly, 'is' . $studly, 'has' . $studly]));
    }
}
