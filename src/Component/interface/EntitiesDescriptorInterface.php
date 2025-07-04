<?php
namespace Aequation\WireBundle\Component\interface;

// PHP
use Closure;

interface EntitiesDescriptorInterface
{
    /**
     * Get all entities.
     *
     * @return array
     */
    public function getEntities(): array;
    public function resetFilters(): static;
    public function findClassname(string $shortname): ?string;
    public function isEntity(string|object $something): bool;
    public function isAbstract(string|object $classname): bool;
    public function getInterfaces(string|object $classname): array;
    public function getTraits(string|object $classname): array;
    public function getParents(string|object $classname): array;
    public function getSubclasses(string|object $classname): array;

    public function enableFilterAppWire(bool $filter = true): static;
    public function isFilterAppWire(): bool;
    public function filterAppWire(array &$classnames): void;

    public function enableFilterFinal(bool $filter = true): static;
    public function isFilterFinal(): bool;
    public function filterFinal(array &$classnames): void;

    public function enableShortnames(bool $shortnames = true): static;
    public function isShortnames(): bool;
    public function transformShortnames(array &$classnames): void;

    public function isAppWireEntity(string|object $objectOrClass): bool;
    public function isBetweenEntity(string|object $objectOrClass): bool;
    public function isTranslationEntity(string|object $objectOrClass): bool;
    public function isFinalEntity(string|object $objectOrClass): bool;

    /**
     * Get all entities by names.
     *
     * @param string $classname
     * @param null|Closure $filter
     * @param bool $andOperator
     * @return array|null
     */
    public function findAll(null|string|array $names, ?Closure $filter = null, bool $andOperator = true): array;

    /**
     * Find final entities by names.
     *
     * @param null|string|array $names
     * @param bool $andOperator
     * @return array
     */
    public function findFinals(null|string|array $names, bool $andOperator = true): array;
    public function findOneFinal(null|string|array $names, bool $andOperator = true): string;
    public function findOneFinalOrNull(null|string|array $names, bool $andOperator = true): ?string;

    public function findHydratableFinals(bool $shortnames = false): array;
    public function findBetweenFinals(bool $shortnames = false): array;
    public function findTranslationFinals(bool $shortnames = false): array;
}