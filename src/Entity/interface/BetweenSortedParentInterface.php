<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;
use Traversable;

interface BetweenSortedParentInterface
{
    public function getSortgroup(?BetweenSortedChildInterface $child = null): string;
    public function isAvailableChildForParent(BetweenSortedChildInterface $item, string $property): bool;
    public function filterAvailableChildsForParent(Collection $items, string $property): Collection;
}