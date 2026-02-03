<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

interface DiscoversPath
{
    public function discoverPath(DiscoveryLocation $location, string $path): void;
}
