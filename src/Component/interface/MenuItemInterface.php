<?php
namespace Aequation\WireBundle\Component\interface;

use Stringable;

interface MenuItemInterface extends Stringable
{
    public function getItemType(): string;
    public function getLevel(): int;
    public function getRootParent(): MenuComponentInterface;
    public function isContextFilterEnabled(): bool;
    /** Simulation for TypedCollection */
    public function count(): int;
}