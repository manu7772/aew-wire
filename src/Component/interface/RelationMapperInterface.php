<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\AssociationMapping;
// PHP
use Closure;
use Twig\Markup;

interface RelationMapperInterface
{
    public function __construct(
        string $classname,
        WireEntityManagerInterface $wireEm
    );

    public function isValid(): bool;
    public function getControls(): OpresultInterface;
    public function getErrorMessages(): array;
    public function getMessagesAsString(?bool $asHtml = null, bool $byTypes = true, null|string|array $msgtypes = null): string|Markup;
    public function getClassname(): string;
    public function getClassmetaData(): ClassMetadata;
    // Report
    public function getReport(): array;
    // Fields
    public function hasField(string $field): bool;
    public function getFieldMapping(string $field): FieldMapping|false;
    public function getFieldMappings(): array;
    public function getRelationValue(object $entity, string $field): null|object|array;
    public function setRelationValue(object $entity, string $field, object|array $value, bool $addToMany = false): void;
    // Relations
    public function getRelationFieldnames(): array;
    public function hasRelation(string $field): bool;
    public function getRelation(string $field): array|false;
    public function getRelationMapping(string $field): AssociationMapping;
    public function isToOneRelation(string $field): bool;
    public function isToManyRelation(string $field): bool;
    public function isRelationCreateOnly(string $field): bool;
    // public function isRelationUpdateOnly(string $field): bool;
    public function isAvailableRelation(string $field, object|string $entity): bool;
    public function getRelationTargetClasses(string $field, bool $onlyInstantiables = true): array|false;
    public function getRelations(?Closure $filter = null): array;

}