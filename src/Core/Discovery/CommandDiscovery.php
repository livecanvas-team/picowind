<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use Picowind\Core\Container\Container;
use Picowind\Core\Discovery\Attributes\Command;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use WP_CLI;

final class CommandDiscovery implements Discovery
{
    use IsDiscovery;

    /** @var array<array<string, mixed>> */
    private array $commands = [];

    public function __construct(
        private Container $container,
    ) {
        $this->discoveryItems = new DiscoveryItems();
    }

    /**
     * @param ClassReflector $classReflector
     */
    public function discover(DiscoveryLocation $discoveryLocation, ClassReflector $classReflector): void
    {
        // Check for class-level Command attribute (invokable command)
        $classCommandAttribute = $classReflector->getAttribute(Command::class);

        if (null !== $classCommandAttribute) {
            $this->discoveryItems->add($discoveryLocation, [
                'type' => 'class',
                'className' => $classReflector->getName(),
                'method' => '__invoke',
                'name' => $classCommandAttribute->name ?? $this->generateCommandName($classReflector->getName()),
                'description' => $classCommandAttribute->description ?? '',
                'aliases' => $classCommandAttribute->aliases,
                'synopsis' => $classCommandAttribute->synopsis,
                'when' => $classCommandAttribute->when,
            ]);
        }

        // Check for method-level Command attributes
        foreach ($classReflector->getPublicMethods() as $methodReflector) {
            $methodCommandAttribute = $methodReflector->getAttribute(Command::class);

            if (null !== $methodCommandAttribute) {
                $this->discoveryItems->add($discoveryLocation, [
                    'type' => 'method',
                    'className' => $classReflector->getName(),
                    'method' => $methodReflector->getName(),
                    'name' => $methodCommandAttribute->name ?? $this->generateMethodCommandName($classReflector->getName(), $methodReflector->getName()),
                    'description' => $methodCommandAttribute->description ?? '',
                    'aliases' => $methodCommandAttribute->aliases,
                    'synopsis' => $methodCommandAttribute->synopsis,
                    'when' => $methodCommandAttribute->when,
                ]);
            }
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $discoveryItem) {
            if (is_array($discoveryItem)) {
                /** @var array<string, mixed> $item */
                $this->commands[] = [
                    'className' => $discoveryItem['className'],
                    'name' => $discoveryItem['name'],
                    'description' => $discoveryItem['description'],
                    'aliases' => $discoveryItem['aliases'],
                    'synopsis' => $discoveryItem['synopsis'],
                    'when' => $discoveryItem['when'],
                    'type' => $discoveryItem['type'],
                    'method' => $discoveryItem['method'],
                ];
            }
        }
    }

    public function registerCommands(): void
    {
        if (! class_exists('WP_CLI')) {
            return;
        }

        foreach ($this->commands as $command) {
            $this->registerCommandFromData($command);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function registerCommandFromData(array $data): void
    {
        assert(is_string($data['className']));
        assert(is_string($data['name']));
        assert(is_string($data['description']));
        assert(is_array($data['aliases']));
        assert(is_string($data['synopsis']) || $data['synopsis'] === null);
        assert(is_string($data['when']) || $data['when'] === null);
        assert(is_string($data['type']));
        assert(is_string($data['method']));

        $className = $data['className'];
        $commandName = $data['name'];
        $description = $data['description'];
        $aliases = $data['aliases'];
        $synopsis = $data['synopsis'];
        $when = $data['when'];
        $type = $data['type'];
        $methodName = $data['method'];

        // Try to get from container first, otherwise instantiate directly
        if ($this->container->has($className)) {
            $instance = $this->container->get($className);
        } else {
            // Instantiate with manual dependency resolution for commands not in DI container
            if (! class_exists($className)) {
                return;
            }

            try {
                $instance = $this->instantiateWithDependencies($className);
            } catch (Throwable $e) {
                error_log(sprintf('Failed to instantiate command class %s: ', $className) . $e->getMessage());
                return;
            }
        }

        assert(is_object($instance));

        // Create callable based on type
        if ('class' === $type) {
            // For class-level commands, use the instance directly (invokable)
            $callable = $instance;
        } else {
            // For method-level commands, create array callable [object, method]
            $callable = [$instance, $methodName];
        }

        $args = [
            'shortdesc' => $description,
        ];

        if ($synopsis !== null) {
            $args['synopsis'] = $synopsis;
        }

        if ($when !== null) {
            $args['when'] = $when;
        }

        WP_CLI::add_command($commandName, $callable, $args);

        // Register aliases with the same configuration
        foreach ($aliases as $alias) {
            assert(is_string($alias));
            WP_CLI::add_command($alias, $callable, $args);
        }
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    private function generateCommandName(string $className): string
    {
        // Convert class name to command name
        // e.g., "Picowind\Commands\ThemeCommand" -> "picowind theme"
        $parts = explode('\\', $className);
        $commandClass = end($parts);

        // Remove "Command" suffix if present
        if (str_ends_with($commandClass, 'Command')) {
            $commandClass = substr($commandClass, 0, -7);
        }

        // Convert PascalCase to kebab-case
        $commandName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $commandClass) ?? '');

        return 'picowind ' . $commandName;
    }

    private function generateMethodCommandName(string $className, string $methodName): string
    {
        // Convert class name to base command name
        $parts = explode('\\', $className);
        $commandClass = end($parts);

        // Remove "Command" suffix if present
        if (str_ends_with($commandClass, 'Command')) {
            $commandClass = substr($commandClass, 0, -7);
        }

        // Convert PascalCase to kebab-case for both class and method
        $classCommand = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $commandClass) ?? '');
        $methodCommand = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $methodName) ?? '');

        return sprintf('picowind %s %s', $classCommand, $methodCommand);
    }

    private function instantiateWithDependencies(string $className): object
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        if (null === $constructor) {
            // No constructor, simple instantiation
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $dependencyClassName = $type->getName();

                // Always resolve from container
                $dependencies[] = $this->container->get($dependencyClassName);
            } else {
                // Can't resolve primitive types or builtin types
                throw new RuntimeException(sprintf("Cannot resolve parameter '%s' of type '%s' in class %s", $parameter->getName(), $type?->getName(), $className));
            }
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
