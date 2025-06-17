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

    public function enableFilterAppWire(bool $filter = true): static;
    public function isFilterAppWire(): bool;
    public function filterAppWire(array &$classnames): void;

    public function enableShortnames(bool $shortnames = true): static;
    public function isShortnames(): bool;
    public function transformShortnames(array &$classnames): void;

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

    /**
     * Find instantiable entities by names.
     *
     * @param null|string|array $names
     * @param bool $andOperator
     * @return array
     */
    public function findInstantiables(null|string|array $names, bool $andOperator = true): array;
    public function findOneInstantiable(null|string|array $names, bool $andOperator = true): string;
    public function findOneInstantiableOrNull(null|string|array $names, bool $andOperator = true): ?string;

}