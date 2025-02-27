<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Entity\WireItem;
use Doctrine\Common\Collections\Collection;

// Symfony

interface WireEcollectionInterface extends WireItemInterface
{

    public function getItems(): Collection;
    public function addItem(WireItem $item): bool;
    public function removeItem(WireItem $item): static;
    public function removeItems(): static;
    public function hasItem(WireEntityInterface $item): bool;

    public function isAcceptsItemForEcollection(WireEntityInterface $item, string $property): bool;
    public function filterAcceptedItemsForEcollection(Collection $items, string $property): Collection;

}