<?php
namespace Aequation\WireBundle\Entity\interface;


interface ItemCollectionInterface extends BetweenManyInterface
{
    public function getParent(): WireEcollectionInterface;
    public function getChild(): WireItemInterface;
    public function getPosition(): int;
    public function setPosition(int $position): static;
    public function updateSortgroup(): static;
    public function getSortgroup(): string;
    public function setSortgroup(string $sortgroup): static;
    public function preRemove(): static;
}