<?php
namespace Aequation\WireBundle\Component\interface;

// PHP
use SplFileInfo;

interface HydradataCollectionInterface extends TypedCollectionInterface
{
    public function registerDataFile(string|SplFileInfo $file): bool;
    public function sortElements(): static;
    public function getByName(string $name): ?HydradataItemsInterface;
    public function getByShortname(string $shortname): ?HydradataItemsInterface;
    public function getByIndex(int $index): ?HydradataItemsInterface;
    public function isValid(): bool;
    public function getHydrationReadyIndexes(): array;
}