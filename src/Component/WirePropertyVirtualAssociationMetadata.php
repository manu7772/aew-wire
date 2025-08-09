<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\TypedCollectionInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
use Aequation\WireBundle\Component\interface\WirePropertyAssociationMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyVirtualAssociationMetadataInterface;
// Symfony
use Doctrine\ORM\Mapping\AssociationMapping;
// PHP
use InvalidArgumentException;

class WirePropertyVirtualAssociationMetadata extends WirePropertyAssociationMetadata implements WirePropertyVirtualAssociationMetadataInterface
{

    // Parent class scope
    public readonly string $mapping_name;
    public readonly WireClassMetadataInterface $wCmd;
    // Parent class scope: virtual utilities
    public readonly false|array $virtualChilds;
    public readonly false|WireClassMetadataCollectionInterface $targetFinals;

    public readonly string $virtual_name;
    public readonly ?AssociationMapping $mapping;
    public readonly false|WireClassMetadataInterface $targetEntity;
    public readonly false|WireClassMetadataInterface $betweenTargetEntity;
    public readonly false|string $inversedBy;
    public readonly false|string $mappedBy;

    public function __construct(
        string $class,
        string $property,
        WireClassMetadataInterface $wCmd,
        public readonly WirePropertyAssociationMetadataInterface $parent,
    ) {
        if($this->parent instanceof static) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: parent cannot be an instance of %s', [__METHOD__, __LINE__, static::class]));
        }
        $this->virtualChilds = false; // No virtual childs for this class
        parent::__construct($class, $property, $wCmd);
        $this->virtual_name = $this->parent->current_virtual_property_name; // --> The virtual property name
        $this->mapping_name = $this->name;
        $this->mapping = $this->parent->mapping;
        $this->inversedBy = $this->parent->inversedBy;
        $this->mappedBy = $this->parent->mappedBy;
        $this->targetEntity = $this->parent->targetEntity;
        $this->betweenTargetEntity = $this->parent->betweenTargetEntity;
        // if($this->wCmd->isDev) dump($this->getTargetNames());
    }

    public function getParent(): WirePropertyAssociationMetadataInterface
    {
        return $this->parent;
    }

    public function getVirtualName(): string
    {
        return $this->virtual_name;
    }

    /**
     * Get the target entities (WireClassMetadataCollectionInterface) of the relation.
     * If $findOutVirtuals is true and the relation is a BetweenSortedInterface, it will return the final names of the child classes.
     * 
     * @param string $type The type of the relation, default is 'final'.
     * @param bool $findOutVirtuals If true, it will find out the virtual names of the relation.
     * @return false|array The target entities (WireClassMetadataCollectionInterface) of the relation, or false if the relation is not a relation.
     */
    public function getTargetEntities(string $type = 'final'): false|WireClassMetadataCollectionInterface
    {
        if(!isset($this->targetFinals)) {
            // Check by WireRelationMapping Attribute data if exists
            $this->targetFinals = $this->wCmdm
                ->setSearchMode($type)
                ->setTypeCompareOr() // IMPORTANT!!!
                ->filterClasses($this->virtual_mapping->getFieldPropertyRequirements($this->mapping_name, $this->virtual_name))
                ->filter(fn (WireClassMetadataInterface $wCmd) => is_a($wCmd->name, $this->betweenTargetEntity->name, true))
                ;
        }
        return $this->targetFinals;
    }

}