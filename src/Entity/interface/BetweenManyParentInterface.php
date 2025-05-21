<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface BetweenManyParentInterface
{
    public function getItemPosition(WireItemInterface $item): int|false;
    public function setItemPosition(WireItemInterface $item, int $position): static;
    public function getSortgroup(?BetweenManyChildInterface $child = null): string;
    public function getItems(): Collection;
    public function getActiveItems(): Collection;
    public function addItem(WireItemInterface $item): static;
    public function removeItem(WireItemInterface $item): static;
    public function removeItems(): static;
    public function hasItem(WireItemInterface $item): bool;
    public function isAcceptsChildForParent(WireItemInterface $item, string $property): bool;
    public function filterAcceptedChildsForParent(Collection $items, string $property): Collection;
}