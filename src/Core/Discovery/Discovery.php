<?php

declare (strict_types=1);
namespace Picowind\Core\Discovery;

interface Discovery
{
    public function discover(\Picowind\Core\Discovery\DiscoveryLocation $discoveryLocation, \Picowind\Core\Discovery\ClassReflector $classReflector): void;
    public function apply(): void;
    public function getItems(): \Picowind\Core\Discovery\DiscoveryItems;
    public function setItems(\Picowind\Core\Discovery\DiscoveryItems $discoveryItems): void;
}
