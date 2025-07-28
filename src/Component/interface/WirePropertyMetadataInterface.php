<?php
namespace Aequation\WireBundle\Component\interface;

// Symfony
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\AssociationMapping;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;


interface WirePropertyMetadataInterface
{
    public function getAccessor(): PropertyAccessorInterface;
    public function getClassMetadata(): ?ClassMetadata;
    public function getName(): string;
    public function isMapped(): bool;
    public function getMapping(): null|AssociationMapping|FieldMapping;
    public function isEmbedded(): bool;
    public function getMappingName(): string;
    public function __call($name, $arguments);
    public function __get($name);
    public function __isset($name);
    public function isField(): bool;
    // public function getFieldMapping(): ?FieldMapping;
    public function isId(): bool;
    public function isRelation(): bool;
    public function isBetweenRelation(): bool;
    public function getBetweenTarget(): false|WireClassMetadataInterface;
    public function getTargetNames(string $type = 'final'): false|array;
    public function isCascadePersist(): bool;
    // public function isOwningSide(): bool;
    public function getValue(object $entity): mixed;
    public function setValue(object $entity, mixed $value): void;
}