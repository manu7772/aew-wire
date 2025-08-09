<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\interface\BetweenSortedChildInterface;
use Doctrine\Common\Collections\Collection;

trait BetweenSortedParent
{
    // Sortgroup
    public function getSortgroup(?BetweenSortedChildInterface $child = null): string
    {
        return $this->getEuid();
    }

    public function isAvailableChildForParent(BetweenSortedChildInterface $item, string $property): bool
    {   
        if($item !== $this && defined('static::ITEMS_ACCEPT')) {
            $mapping = new WireRelationMapping(constant('static::ITEMS_ACCEPT'));
            foreach ($mapping->mapping['properties'][$property]['require'] as $classes) {
                foreach ($classes as $class) {
                    if(is_a($item, $class)) return true;
                }
            }
        }
        return false;
    }

    public function filterAvailableChildsForParent(Collection $items, string $property): Collection
    {
        return $items->filter(fn($item) => $item !== $this && $this->isAvailableChildForParent($item, $property));
    }
}