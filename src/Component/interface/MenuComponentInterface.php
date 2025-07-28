<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// PHP
use Traversable;

interface MenuComponentInterface extends Traversable
{
    public function contextFilter(bool $enabled): static;
    public function isContextFilterEnabled(): bool;
    public function getRouterInfo(): RouterInfoInterface;
    public function getAppWire(): AppWireServiceInterface;
    public function getMaxLevels(): int;
    public function setMaxLevels(int $max_levels): static;

}