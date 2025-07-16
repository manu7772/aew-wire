<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Dto\interfaace\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\EntityRepository;
// PHP
use ReflectionClass;
use Stringable;
use Symfony\Component\ObjectMapper\Attribute\Map;

interface WireClassMetadataInterface extends Stringable
{
    public function getName(): string;
    public function getShortName(): string;
    public function getReflectionClass(): ReflectionClass;
    public function isFinal(): bool;
    public function isInstantiable(): bool;
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
    public function getSubclasses(bool $onlyManaged = false): array;
    public function getSubclassesNames(bool $onlyManaged = false): array;
    public function getInterfaces(): array;
    public function implementsInterfaces(array $interfaces, bool $typeCompareAnd = true): bool;
    public function getInterfacesNames(): array;
    public function getTraits(): array;
    public function getTraitsNames(): array;
    public function newInstance(?array $data = null, array $context = []): object;
    public function newModel(?array $data = null, array $context = []): object;
    // public function newDto(array $data = [], array $context = []): WireEntityDtoInterface;
    public function isType(string $type): bool;
    public function isAppwire(): bool;
    public function isBetween(): bool;
    public function isTranslation(): bool;
    public function isHydratable(): bool;
    public function getService(): ?WireEntityServiceInterface;
    public function getRepository(): EntityRepository;
    public function getDtoSourceMaps(): array;
    public function getFirstDtoSourceMap(): ?Map;
    public function getDtoTargetMaps(): array;
    public function getFirstDtoTargetMap(): ?Map;
    public function getProperty(string $name): ?WirePropertyMetadataInterface;
    public function getTarget(string $relation): WireClassMetadataInterface;
    public function getTargetName(string $relation): string;
    public function getTargetNames(string $relation, string $type = 'final'): array;
    public function getOrphanRelations(): array;
}