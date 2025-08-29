<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Attribute\WireRelationMapping;
use Aequation\WireBundle\Entity\interface\BetweenSortedInterface;
use Aequation\WireBundle\Component\interface\TypedCollectionInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataCollectionInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedParentInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyAssociationMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyVirtualAssociationMetadataInterface;
// Symfony
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\OwningSideMapping;
use Doctrine\ORM\Mapping\InverseSideMapping;
use Exception;
// PHP
use InvalidArgumentException;

class WirePropertyAssociationMetadata extends WirePropertyAbstractMetadata implements WirePropertyAssociationMetadataInterface
{

    // Parent class scope
    public readonly string $mapping_name;
    public readonly false|WireRelationMapping $virtual_mapping;
    // Parent class scope: virtual utilities
    public readonly false|array $virtualChilds;
    public ?string $current_virtual_property_name = null;
    
    public readonly ?AssociationMapping $mapping;
    public readonly false|WireClassMetadataInterface $targetEntity;
    public readonly false|WireClassMetadataInterface $betweenTargetEntity;
    public readonly false|WireClassMetadataCollectionInterface $targetFinals;
    public readonly false|string $inversedBy;
    public readonly false|string $mappedBy;

    public function __construct(
        string $class,
        string $property,
        WireClassMetadataInterface $wCmd,
    ) {
        // dump($wCmd->getVirtualRelationMapping());
        // if($wCmd->isDev) dump($class.'::'.$property);
        parent::__construct($class, $property, $wCmd);
        $this->virtual_mapping = $wCmd->getVirtualRelationMapping();
        if(self::class === static::class) {
            // Not for subclasses
            $this->mapping_name = $this->name;
            $this->mapping = $this->classMetadata->getAssociationMapping($this->mapping_name);
            $this->inversedBy = $this->mapping instanceof OwningSideMapping && strlen((string) $this->mapping->inversedBy) > 0 ? $this->mapping->inversedBy : false; // inverse field name
            $this->mappedBy = $this->mapping instanceof InverseSideMapping && strlen((string) $this->mapping->mappedBy) > 0 ? $this->mapping->mappedBy : false; // mapped field name
            $this->targetEntity = $this->wCmdm->getWireClassMetadata($this->mapping->targetEntity) ?: false;
            if($this->targetEntity?->isBetween() ?? false) {
                foreach ($this->targetEntity->getAssociationMappings() as $map) {
                    if($map->inversedBy !== $this->mapping_name) {
                        $this->betweenTargetEntity = $this->wCmdm->getWireClassMetadata($map->targetEntity);
                        break;
                    }
                }
                // if(!isset($this->betweenTargetEntity) || !$this->betweenTargetEntity) {
                //     dump($this->targetEntity->getAssociationMappings());
                //     throw new InvalidArgumentException(vsprintf('Error %s line %d: could not find between target for between-relation "%s" in class %s.', [__METHOD__, __LINE__, $this->mapping_name, $this->wCmd->name]));
                // }
                $this->betweenTargetEntity ??= false;
            } else {
                $this->betweenTargetEntity = false;
            }
            if($this->virtual_mapping && $this->virtual_mapping->hasField($this->mapping_name) ?? false) {
                // Has virtuals --> add virtual childs
                $virtuals = [];
                foreach ($this->virtual_mapping->getFieldPropertyNames($this->mapping_name) as $name) {
                    // Define current virtual property
                    // if($name !== $this->mapping_name) {
                        $this->current_virtual_property_name = $name;
                        $virtuals[$this->current_virtual_property_name] = new WirePropertyVirtualAssociationMetadata($this->class, $this->mapping_name, $this->wCmd, $this);
                    // } else {
                        // The virtual property name is the same as the mapping name
                    // }
                }
                // Reset current virtual property
                $this->current_virtual_property_name = null;
                $this->virtualChilds = $virtuals;
            } else {
                $this->virtualChilds = false; // No virtual childs for this class
            }
        } else {
            // $this->virtualChilds = false; // No virtual childs for this class
        }
        // if(!$this->inversedBy && !$this->mappedBy) {
        //     throw new InvalidArgumentException(vsprintf('Error %s line %d: property "%s" is not a valid relation in class %s, because could not determine the owning or inverse side.', [__METHOD__, __LINE__, $this->mapping_name, $this->wCmd->name]));
        // }
        // if($this->wCmd->isDev && !empty($this->virtualChilds)) dd($this, $this->getTargetNames());
    }


    public function isVirtualChild(): bool
    {
        return $this instanceof WirePropertyVirtualAssociationMetadataInterface;
    }

    public function getVirtualName(): string
    {
        return $this->name;
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
    /** RELATIONS                                                                                     */
    /**************************************************************************************************/

    public function isRelation(): bool
    {
        return $this->classMetadata?->hasAssociation($this->mapping_name) ?? false;
    }

    public function isBetweenRelation(): bool
    {
        $is = $this->isRelation() && $this->virtual_mapping
            ? is_a($this->mapping->targetEntity, BetweenSortedInterface::class, true)
            : false;
        if($is && !is_a($this->wCmd->name, BetweenSortedParentInterface::class, true)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: property "%s" is a relation of type %s but the parent class %s does not implement %s!', [__METHOD__, __LINE__, $this->name, $this->mapping_name, $this->wCmd->name, BetweenSortedParentInterface::class]));
        }
        return $is;
    }

    public function getBetweenTargetEntity(): false|WireClassMetadataInterface
    {
        return $this->betweenTargetEntity;
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
            if($this->isBetweenRelation()) {
                $requirements = $this->virtual_mapping->getFieldRequierements($this->mapping_name);
                if(!$requirements && !$this->betweenTargetEntity) {
                    return false;
                }
                $this->targetFinals = $this->wCmdm
                    ->setSearchMode($type)
                    ->setTypeCompareOr() // IMPORTANT!!!
                    ->filterClasses($requirements ?: [$this->betweenTargetEntity->name])
                    ->filter(fn (WireClassMetadataInterface $wCmd) => is_a($wCmd->name, $this->betweenTargetEntity->name, true))
                    ;
            } else {
                $this->targetFinals = $this->wCmdm
                    ->setSearchMode($type)
                    ->filterClasses(
                        $this->virtualChilds && isset($this->virtualChilds[$this->mapping_name])
                            ? $this->virtualChilds[$this->mapping_name]->getTargetEntities($type)
                            : [$this->mapping->targetEntity]
                        )
                    ;
            }
        }
        return $this->targetFinals;
    }

    /**
     * Get the target names (array<string>) of the relation.
     * If $findOutVirtuals is true and the relation is a BetweenSortedInterface, it will return the final names of the child classes.
     * 
     * @param string $type The type of the relation, default is 'final'.
     * @return false|array The target names (array<string>) of the relation, or false if the relation is not a relation.
     */
    public function getTargetNames(string $type = 'final'): false|array
    {
        $targets = $this->getTargetEntities($type);
        return $targets instanceof TypedCollectionInterface
            ? $targets->mapSingleValue('name')
            : false;
    }

    public function isCascadePersist(): bool
    {
        return $this->isRelation() ? in_array('persist', $this->mapping->cascade, true) : false;
    }

    public function isOrphanRemoval(): bool
    {
        return $this->isRelation() ? $this->mapping->orphanRemoval : false;
    }


}