<?php
namespace Aequation\WireBundle\Entity\interface;


interface BetweenSortedInterface
{
    public function getParent(): object;
    public function getChild(): object;
    public function getPosition(): int;
    public function setPosition(int $position): static;
    public function updateSortgroup(): static;
    public function getSortgroup(): string;
    public function setSortgroup(string $sortgroup): static;
}