<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Picowind\Core\Container\Container;
use Picowind\Core\Discovery\CommandDiscovery;
use Picowind\Core\Discovery\DiscoveryManager;
use Picowind\Core\Discovery\HookDiscovery;
use RuntimeException;

class Theme
{
    private static ?self $instance = null;

    private ?Container $container = null;

    private ?DiscoveryManager $discoveryManager = null;

    private bool $booted = false;

    public static function get_instance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->boot();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->container = new Container();
        $this->boot_discover();

        if (! $this->container instanceof Container) {
            throw new RuntimeException('Container initialization failed');
        }

        // Early registration of static hooks before container compilation
        $this->each_discovery(HookDiscovery::class, fn ($discovery) => $discovery->registerStaticHooks());

        $this->container->compile();

        // Register remaining hooks and commands after container compilation
        $this->each_discovery(HookDiscovery::class, fn ($discovery) => $discovery->registerHooks());
        $this->each_discovery(CommandDiscovery::class, fn ($discovery) => $discovery->registerCommands());

        $this->booted = true;
        do_action('a!picowind/core/theme:booted', $this);
    }

    public function container(): Container
    {
        if (! $this->container instanceof Container) {
            throw new RuntimeException('Theme not booted yet. Call boot() first.');
        }

        return $this->container;
    }

    private function boot_discover(): void
    {
        if (! $this->container instanceof Container) {
            throw new RuntimeException('Container not initialized');
        }

        $this->discoveryManager = new DiscoveryManager($this->container);
        $this->discoveryManager->discover();
    }

    /**
     * Execute a callback for each discovery of a given type
     *
     * @param class-string $discoveryClass
     */
    private function each_discovery(string $discoveryClass, callable $callback): void
    {
        foreach ($this->discoveryManager->getDiscoveries() as $discovery) {
            if ($discovery instanceof $discoveryClass) {
                $callback($discovery);
            }
        }
    }
}
