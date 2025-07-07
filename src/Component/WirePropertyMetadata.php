<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyMetadataInterface;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
// PHP
use ReflectionProperty;

class WirePropertyMetadata implements WirePropertyMetadataInterface
{
    public readonly string $name;
    public readonly PropertyAccessorInterface $accessor;
    protected readonly null|AssociationMapping|FieldMapping $relationMapping;

    public function __construct(
        public readonly ReflectionProperty $property,
        public readonly WireClassMetadataInterface $wCmd,
    )
    {
        $this->name = $property->name;
    }

    public function getAccessor(): PropertyAccessorInterface
    {
        return $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->wCmd->classMetadata;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMapped(): bool
    {
        return $this->isField() || $this->isRelation();
    }

    public function getRelationMapping(): null|AssociationMapping|FieldMapping
    {
        return $this->relationMapping ??= $this->getFieldMapping() ?? $this->getAssociationMapping() ?? null;
    }


    /************************************************************************************************************/
    /** INHERITED FROM CLASSMETADATA                                                                            */
    /************************************************************************************************************/

    public function __call($name, $arguments)
    {
        return $this->getRelationMapping()->$name(...$arguments);
    }

    public function __get($name)
    {
        return $this->getRelationMapping()->$name;
    }

    public function __isset($name)
    {
        return isset($this->getRelationMapping()->$name);
    }


    /**************************************************************************************************/
    /** FIELDS                                                                                        */
    /**************************************************************************************************/

    public function isField(): bool
    {
        return $this->getClassMetadata()->hasField($this->name);
    }

    public function getFieldMapping(): ?FieldMapping
    {
        return $this->isField() ? $this->getClassMetadata()->getFieldMapping($this->name) : null;
    }

    public function isId(): bool
    {
        if(!$this->isField()) {
            return false;
        }
        return $this->getClassMetadata()->isIdentifier($this->name);
    }


    /**************************************************************************************************/
    /** RELATIONS                                                                                     */
    /**************************************************************************************************/

    public function isRelation(): bool
    {
        return $this->getClassMetadata()->hasAssociation($this->name);
    }

    public function getAssociationMapping(): ?AssociationMapping
    {
        return $this->isRelation() ? $this->getClassMetadata()->getAssociationMapping($this->name) : null;
    }

    public function isToOne(): bool
    {
        return $this->isRelation() ? $this->getAssociationMapping()->isToOne() : false;
    }

    public function isToMany(): bool
    {
        return $this->isRelation() ? $this->getAssociationMapping()->isToMany() : false;
    }

    public function isOrphanRemoval(): bool
    {
        return $this->isRelation() ? $this->getAssociationMapping()->orphanRemoval : false;
    }

    public function isCascadePersist(): bool
    {
        return ($mapping = $this->getAssociationMapping()) ? in_array('persist', $mapping->cascade, true) : false;
    }

    public function isOwningSide(): bool
    {
        return $this->isRelation() ? $this->getAssociationMapping()->isOwningSide() : false;
    }


    /**************************************************************************************************/
    /** GETTER / SETTER                                                                               */
    /**************************************************************************************************/

    public function getValue(object $entity): mixed
    {
        return $this->getAccessor()->getValue($entity, $this->name);
    }

    public function setValue(object $entity, mixed $value): void
    {
        $this->getAccessor()->setValue($entity, $this->name, $value);
    }

}