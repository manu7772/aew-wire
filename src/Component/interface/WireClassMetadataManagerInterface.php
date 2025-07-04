<?php
namespace Aequation\WireBundle\Component\interface;

use Doctrine\ORM\EntityRepository;

interface WireClassMetadataManagerInterface
{
    public function isInitialized(): bool;
    public function resetFilters(): static;
    public function setSearchMode(string $mode): static;
    public function getSearchMode(): string;
    public function resetSearchMode(): static;
    public function setTypeCompare(bool $typeCompare): static;
    public function setTypeCompareAnd(): static;
    public function setTypeCompareOr(): static;
    public function getTypeCompare(): bool;
    public function resetTypeCompare(): static;
    public function filterClasses(array $interfaces = [], ?WireClassMetadataCollectionInterface $results = null): WireClassMetadataCollectionInterface;
    public function getRepository(string $classname): ?EntityRepository;
    public function getWireClassMetadatas(): WireClassMetadataCollectionInterface;
    public function getWireClassMetadata(string $classname): ?WireClassMetadataInterface;
    public function getTargetName(string $classname, string $relation): string;
    public function getTargetFinalNames(string $classname, string $relation): array;
}