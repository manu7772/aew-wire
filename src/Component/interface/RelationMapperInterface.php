<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
// PHP
use Closure;
use Doctrine\ORM\Mapping\AssociationMapping;
use Twig\Markup;

interface RelationMapperInterface
{
    public function __construct(
        string $classname,
        WireEntityManagerInterface $wireEm,
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
    // Relations
    public function getRelationFieldnames(): array;
    public function hasRelation(string $field): bool;
    public function getRelation(string $field): array|false;
    public function getRelationMapping(string $field): AssociationMapping;
    public function isToOneRelation(string $field): bool;
    public function isToManyRelation(string $field): bool;
    public function isRelationCreateOnly(string $field): bool;
    public function isAvailableRelation(string $field, object|string $entity): bool;
    public function getRelationTargetClasses(string $field, bool $onlyInstantiables = true): array|false;
    public function getRelations(?Closure $filter = null): array;

}