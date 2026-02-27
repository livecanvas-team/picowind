<?php

namespace PicowindDeps\Timber\Integration;

use PicowindDeps\Timber\Integration\CLI\TimberCommand;
use WP_CLI;
/**
 * Class WpCliIntegration
 *
 * Adds a "timber" command to WP CLI.
 */
class WpCliIntegration implements IntegrationInterface
{
    public function should_init(): bool
    {
        return \defined('PicowindDeps\WP_CLI') && \class_exists('WP_CLI');
    }
    public function init(): void
    {
        WP_CLI::add_command('timber', TimberCommand::class);
    }
}
