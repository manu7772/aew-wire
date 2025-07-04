<?php
namespace Aequation\WireBundle\Component\interface;

use ReflectionClass;

interface WireClassMetadataInterface
{
    public function getName(): string;
    public function getShortName(): string;
    public function getReflectionClass(): ReflectionClass;
    public function isFinal(): bool;
    public function isManaged(): bool;
    public function isAbstract(): bool;
    public function getInfo(): array;
    public function __call($name, $arguments);
    public function __get($name);
    public function __isset($name);
    public function getParent(): ?static;
    public function getParentManaged(): ?static;
    public function getParentName(): ?string;
    public function getParents(): array;
    public function getParentsNames(): array;
    public function getSubclasses(): array;
    public function getSubclassesNames(): array;
    public function getInterfaces(): array;
    public function implementsInterfaces(array $interfaces, bool $typeCompareAnd = true): bool;
    public function getInterfacesNames(): array;
    public function getTraits(): array;
    public function getTraitsNames(): array;
    public function isType(string $type): bool;
    public function isAppwire(): bool;
    public function isBetween(): bool;
    public function isTranslation(): bool;
    public function isHydratable(): bool;
    // Association mapping
    public function getTarget(string $relation): WireClassMetadataInterface;
    public function getTargetName(string $relation): string;
    public function getTargetFinalNames(string $relation): array;
}