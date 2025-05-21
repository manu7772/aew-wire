<?php
namespace Aequation\WireBundle\Entity\interface;


interface WebsectionCollectionInterface extends BetweenManyInterface
{
    public function __construct(WireWebpageInterface $webpage, WireWebsectionInterface $websection);
    public function getWebpage(): WireWebpageInterface;
    public function getWebsection(): WireWebsectionInterface;
    public function getPosition(): int;
    public function setPosition(int $position): static;
    public function updateSortgroup(): static;
    public function getSortgroup(): string;
    public function setSortgroup(string $sortgroup): static;
}