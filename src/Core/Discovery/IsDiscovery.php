<?php

declare (strict_types=1);
namespace Picowind\Core\Discovery;

trait IsDiscovery
{
    protected \Picowind\Core\Discovery\DiscoveryItems $discoveryItems;
    public function getItems(): \Picowind\Core\Discovery\DiscoveryItems
    {
        return $this->discoveryItems;
    }
    public function setItems(\Picowind\Core\Discovery\DiscoveryItems $discoveryItems): void
    {
        $this->discoveryItems = $discoveryItems;
    }
}
