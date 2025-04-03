<?php
namespace Aequation\WireBundle\Entity\interface;


interface RelinkCollectionInterface extends BetweenManyInterface
{
    public function __construct_baserelinkcollection(TraitRelinkableInterface $parent, WireRelinkInterface $relink);
    public function getParent(): TraitRelinkableInterface;
    public function getRelink(): WireRelinkInterface;
    public function getPosition(): int;
    public function setPosition(int $position): static;
    public function updateSortgroup(): static;
    public function getSortgroup(): string;
    public function setSortgroup(string $sortgroup): static;
}