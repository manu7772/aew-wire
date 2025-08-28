<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\TypedCollectionInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Component\interface\WirePropertyAbstractMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyVirtualAssociationMetadataInterface;
use Aequation\WireBundle\Component\interface\WireVirtualPropertyMetadataInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedParentInterface;
use ArrayAccess;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\InverseSideMapping;
use Doctrine\ORM\Mapping\OwningSideMapping;
use Exception;
use InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
// PHP
use ReflectionProperty;

abstract class WirePropertyAbstractMetadata extends ReflectionProperty implements WirePropertyAbstractMetadataInterface
{
    public string $name;
    public readonly string $property_name;
    public readonly string $mapping_name;
    public readonly ?ClassMetadata $classMetadata;
    public readonly WireClassMetadataInterface $wCmd;
    public readonly WireClassMetadataManagerInterface $wCmdm;
    public readonly PropertyAccessorInterface $accessor;
    // Virtual utilities
    public readonly false|array $virtualChilds;
    public ?string $current_virtual_property_name = null;


    public function __construct(
        string $class,
        string $property,
        WireClassMetadataInterface $wCmd,
    )
    {
        // dump(static::class, self::class);
        parent::__construct($class, $property);
        $this->wCmd = $wCmd;
        $this->wCmdm = $this->wCmd->getWireClassMetadataManager();
        $this->classMetadata = $this->wCmd->getClassMetadata();
    }

    public function getAccessor(): PropertyAccessorInterface
    {
        return $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
        // return $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->enableExceptionOnInvalidPropertyPath()->enableMagicMethods()->enableMagicMethods()->enableMagicCall()->enableMagicGet()->enableMagicSet()->getPropertyAccessor();
    }

    public function getWireClassMetadataManager(): WireClassMetadataManagerInterface
    {
        return $this->wCmdm;
    }

    public function getClassMetadata(): ?ClassMetadata
    {
        return $this->classMetadata;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMapped(): bool
    {
        return $this->isField() || $this->isRelation();
    }

    public function getMapping(): ArrayAccess
    {
        return $this->mapping;
    }

    public function getMappingName(): string
    {
        return $this->mapping_name;
    }

    public function isEmbedded(): bool
    {
        return false;
    }

    public function getParent()
    {
        return null;
    }

    public function getValue(object|null $object = null): mixed
    {
        try {
            return $this->getAccessor()->getValue($object, $this->mapping_name);
        } catch (Exception $e) {
            // Handle exception if needed
        }
        return null;
    }


    /************************************************************************************************************/
    /** VIRTUALS                                                                                                */
    /************************************************************************************************************/

    abstract public function isVirtualChild(): bool;

    public function hasVirtualChilds(): bool
    {
        return false !== $this->virtualChilds;
    }

    public function getVirtualChilds(): array
    {
        return $this->virtualChilds;
    }


    /************************************************************************************************************/
    /** INHERITED FROM CLASSMETADATA                                                                            */
    /************************************************************************************************************/

    public function __call($name, $arguments)
    {
        return $this->mapping->$name(...$arguments);
    }

    public function __get($name)
    {
        return $this->mapping->$name;
    }

    public function __isset($name)
    {
        return $this->mapping && property_exists($this->mapping, $name);
    }


    /**************************************************************************************************/
    /** GETTER / SETTER                                                                               */
    /**************************************************************************************************/

    // public function getValue(?object $object = null): mixed
    // {
    //     try {
    //         return $this->getAccessor()->getValue($object, $this->mapping_name);
    //     } catch (Exception $e) {
    //         // Handle exception if needed
    //     }
    //     return null;
    // }

    // public function setValue(mixed $objectOrValue, mixed $value = null): void
    // {
    //     $this->getAccessor()->setValue($objectOrValue, $this->mapping_name, $value);
    // }

}