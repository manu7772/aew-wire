<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface WireClassMetadataManagerInterface
{
    public function isInitialized(): bool;
    public function findEntityClassname(string|object $entity): ?string;
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
    public function findByType(string $mode = 'all', array $interfaces = []): WireClassMetadataCollectionInterface;
    public function findInstantiables(array $interfaces = []): WireClassMetadataCollectionInterface;
    public function findManageds(array $interfaces = []): WireClassMetadataCollectionInterface;
    public function findFinals(array $interfaces = []): WireClassMetadataCollectionInterface;
    public function findOneByType(string $mode = 'all', array $interfaces): WireClassMetadataInterface;
    public function findOneInstantiable(array $interfaces): WireClassMetadataInterface;
    public function findOneManaged(array $interfaces): WireClassMetadataInterface;
    public function findOneFinal(array $interfaces): WireClassMetadataInterface;
    public function getService(string|object $classname): ?WireEntityServiceInterface;
    public function getRepository(string $classname): EntityRepository;
    public function newInstance(string $classname, mixed $args): object;
    public function newModel(string $classname, $args): WireEntityInterface;
    public function validateEntity(object $entity,string|GroupSequence|array|null $addGroups = null,Constraint|array|null $constraints = null): ConstraintViolationListInterface;
    public function getWireClassMetadatas(): WireClassMetadataCollectionInterface;
    public function getWireClassMetadata(string|object $classname): ?WireClassMetadataInterface;
    public function getTargetName(string $classname, string $relation): string;
    public function getTargetFinalNames(string $classname, string $relation): array;
}