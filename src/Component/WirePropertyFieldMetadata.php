<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyFieldMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyVirtualFieldMetadataInterface;
// Symfony
use Doctrine\ORM\Mapping\FieldMapping;
use Exception;
// PHP
use ReflectionProperty;

class WirePropertyFieldMetadata extends WirePropertyAbstractMetadata implements WirePropertyFieldMetadataInterface
{

    // Parent class scope
    public readonly string $property_name;
    public readonly string $mapping_name;
    protected readonly FieldMapping $mapping;
    // Parent class scope: virtual utilities
    public readonly false|array $virtualChilds;
    public ?string $current_virtual_property_name = null;

    public readonly array $parts;

    public function __construct(
        string $class,
        string $property,
        WireClassMetadataInterface $wCmd,
        array $parts = [],
    ) {
        parent::__construct($class, $property, $wCmd);
        if(self::class === static::class) {
            // Not for subclasses
            // ...
        }
        $this->parts = $parts;
        $this->mapping_name = implode('.', array_merge([$this->name], $this->parts));
        $this->property_name = implode('_', array_merge([$this->name], $this->parts));
        $this->mapping = $this->classMetadata->getFieldMapping($this->mapping_name);
    }

    public function isVirtualChild(): bool
    {
        return $this instanceof WirePropertyVirtualFieldMetadataInterface;
    }

    public function isEmbedded(): bool
    {
        return count($this->parts) > 0;
    }

    public function getValue(object|null $object = null): mixed
    {
        return parent::getValue($object);
        // try {
        //     return $this->getAccessor()->getValue($object, $this->mapping_name);
        // } catch (Exception $e) {
        //     // Handle exception if needed
        // }
        // return null;
    }

    /**************************************************************************************************/
    /** FIELDS                                                                                        */
    /**************************************************************************************************/

    // public function isField(): bool
    // {
    //     return $this->classMetadata?->hasField($this->mapping_name) ?? false;
    // }

    public function isId(): bool
    {
        if(!$this->isField()) {
            return false;
        }
        return $this->classMetadata?->isIdentifier($this->mapping_name) ?? false;
    }



}