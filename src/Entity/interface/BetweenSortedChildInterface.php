<?php
namespace Aequation\WireBundle\Entity\interface;


interface BetweenSortedChildInterface
{
    public function getPosition(): int|false;
    public function setPosition(int $position): bool;
}