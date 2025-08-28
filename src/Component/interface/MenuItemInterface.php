<?php
namespace Aequation\WireBundle\Component\interface;

use Stringable;

interface MenuItemInterface extends Stringable
{
    public function isValid(): bool;
}