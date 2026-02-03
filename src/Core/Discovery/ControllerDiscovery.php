<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use Picowind\Core\Container\Container;
use Picowind\Core\Discovery\Attributes\Controller;
use Picowind\Core\Discovery\Attributes\Route;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use WP_REST_Request;

final class ControllerDiscovery implements Discovery
{
    use IsDiscovery;

    private array $controllers = [];

    private array $routes = [];

    public function __construct(
        private Container $container,
    ) {
        $this->discoveryItems = new DiscoveryItems();
    }

    public function discover(DiscoveryLocation $discoveryLocation, ClassReflector $classReflector): void
    {
        $controllerAttribute = $classReflector->getAttribute(Controller::class);

        if (null === $controllerAttribute) {
            return;
        }

        $routes = [];
        foreach ($classReflector->getPublicMethods() as $methodReflector) {
            $routeAttributes = $methodReflector->getAttributes(Route::class);

            foreach ($routeAttributes as $routeAttribute) {
                $routes[] = [
                    'methodName' => $methodReflector->getName(),
                    'path' => $routeAttribute->path,
                    'methods' => $routeAttribute->methods,
                    'name' => $routeAttribute->name,
                    'middleware' => $routeAttribute->middleware ?? [],
                    'permission_callback' => $routeAttribute->permission_callback,
                    'args' => $routeAttribute->args ?? [],
                ];
            }
        }

        $this->discoveryItems->add($discoveryLocation, [
            'className' => $classReflector->getName(),
            'prefix' => $controllerAttribute->prefix ?? '',
            'namespace' => $controllerAttribute->namespace ?? 'picowind/v1',
            'middleware' => $controllerAttribute->middleware ?? [],
            'routes' => $routes,
        ]);
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $discoveryItem) {
            $this->registerControllerFromData($discoveryItem);
        }

        $this->registerRestRoutes();
    }

    private function registerControllerFromData(array $data): void
    {
        $className = $data['className'];
        $prefix = $data['prefix'];
        $namespace = $data['namespace'];
        $controllerMiddleware = $data['middleware'];

        if (! $this->container->has($className) && ! $this->container->is_compiled()) {
            $this->container->register($className, $className);
        }

        $this->controllers[$className] = [
            'class' => $className,
            'prefix' => $prefix,
            'namespace' => $namespace,
            'middleware' => $controllerMiddleware,
        ];

        foreach ($data['routes'] as $routeData) {
            $fullPath = $prefix . $routeData['path'];

            $this->routes[] = [
                'namespace' => $namespace,
                'path' => $fullPath,
                'methods' => $routeData['methods'],
                'controller' => $className,
                'action' => $routeData['methodName'],
                'name' => $routeData['name'],
                'middleware' => array_merge($controllerMiddleware, $routeData['middleware']),
                'permission_callback' => $routeData['permission_callback'],
                'args' => $routeData['args'],
            ];
        }
    }

    private function registerRestRoutes(): void
    {
        add_action('rest_api_init', function () {
            foreach ($this->routes as $route) {
                register_rest_route(
                    $route['namespace'],
                    $route['path'],
                    [
                        'methods' => $route['methods'],
                        'callback' => $this->createRouteCallback($route),
                        'permission_callback' => $this->createPermissionCallback($route),
                        'args' => $route['args'],
                    ]
                );
            }
        });
    }

    private function createRouteCallback(array $route): callable
    {
        return function (WP_REST_Request $request) use ($route) {
            $className = $route['controller'];

            // Try to get from container first, otherwise instantiate directly
            if ($this->container->has($className)) {
                $controller = $this->container->get($className);
            } else {
                $controller = $this->instantiateWithDependencies($className);
            }

            $action = $route['action'];

            $response = $this->applyMiddleware($route['middleware'], $request, function ($request) use ($controller, $action) {
                return $controller->$action($request);
            });

            return $response;
        };
    }

    private function createPermissionCallback(array $route): callable
    {
        return function () use ($route) {
            if ($route['permission_callback']) {
                if (function_exists($route['permission_callback'])) {
                    return call_user_func($route['permission_callback']);
                }

                return current_user_can($route['permission_callback']);
            }

            if (in_array('auth', $route['middleware'], true)) {
                return is_user_logged_in();
            }

            if (in_array('admin', $route['middleware'], true)) {
                return current_user_can('manage_options');
            }

            return true;
        };
    }

    private function applyMiddleware(array $middleware, WP_REST_Request $request, callable $next)
    {
        return $next($request);
    }

    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function instantiateWithDependencies(string $className): object
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        if (null === $constructor) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $dependencyClassName = $type->getName();
                $dependencies[] = $this->container->get($dependencyClassName);
            } else {
                throw new RuntimeException(sprintf("Cannot resolve parameter '%s' of type '%s' in class %s", $parameter->getName(), $type?->getName(), $className));
            }
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
