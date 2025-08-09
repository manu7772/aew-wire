<?php
namespace Aequation\WireBundle\Entity\interface;

// Symfony
use Doctrine\Common\Collections\Collection;
// PHP
use IteratorAggregate;
use Traversable;

interface WireEcollectionInterface extends WireItemInterface, BetweenSortedParentInterface, IteratorAggregate, Traversable
{
    public function getChilds(): Collection;
    public function setChilds(iterable $childs): static;
    public function addChild(WireItemInterface|WireItemCollectionInterface $child): static;
    public function hasChild(WireItemCollectionInterface $child): bool;
    public function removeChild(WireItemCollectionInterface $child): static;
    public function removeChilds(): static;

    public function setItems(iterable $items): static;
    public function getItems(): Collection;
    public function getActiveItems(): Collection;
    public function addItem(WireItemInterface $item): static;
    public function removeItem(WireItemInterface $item): static;
    public function removeItems(): static;
    public function hasItem(WireItemInterface $item): bool;

    // Childs status
    public function isEmpty(): bool;
    public function hasChilds(): bool;
    public function count(): int;
    public function getIterator(): Traversable;
    // Items
    public function getItemPosition(WireItemInterface $item): int|false;
    public function setItemPosition(WireItemInterface $item, int $position): static;

}