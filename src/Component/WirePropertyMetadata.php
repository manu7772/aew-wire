<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WirePropertyMetadataInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
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
    public readonly string $mapping_name;
    public readonly PropertyAccessorInterface $accessor;
    protected readonly null|AssociationMapping|FieldMapping $mapping;
    public readonly ?ClassMetadata $classMetadata;
    public readonly false|string $inversedBy;
    public readonly false|array $targetFinals;
    public readonly false|array $between_map;
    public readonly false|array $target_names;
    public readonly false|WireClassMetadataInterface $targetEntity;
    public readonly false|WireClassMetadataInterface $betweenTarget;

    public function __construct(
        public readonly ReflectionProperty $property,
        public readonly WireClassMetadataInterface $wCmd,
        public array $parts = [],
    )
    {
        $this->classMetadata = $this->wCmd->getClassMetadata();
        $name = $property->name;
        $this->mapping_name = implode('.', array_merge([$name], $this->parts));
        $this->name = implode('_', array_merge([$name], $this->parts));
        if($this->isField()) {
            $this->mapping = $this->getClassMetadata()->getFieldMapping($this->mapping_name);
        } else if($this->isRelation()) {
            $this->mapping = $this->getClassMetadata()->getAssociationMapping($this->mapping_name);
            $this->inversedBy = $this->mapping->inversedBy ?? false;
            // Find between target
            $this->targetEntity = $this->wCmd->getWireClassMetadataManager()->getWireClassMetadata($this->mapping->targetEntity);
            if($this->targetEntity->isBetween()) {
                foreach ($this->targetEntity->getAssociationMappings() as $map) {
                    if($map->inversedBy !== $this->mapping_name) {
                        $this->betweenTarget = $this->wCmd->getWireClassMetadataManager()->getWireClassMetadata($map->targetEntity);
                    }
                }
            }
        } else {
            $this->mapping = null;
        }
        // if(preg_match('/^twigfile/', $this->name)) dd($this);
        // Defaults values is not set
        $this->inversedBy ??= false;
        $this->between_map = $this->wCmd->getRelativeAssociationData($this->name);
        $this->betweenTarget ??= false;
        $this->targetFinals = $this->getTargetNames('final');
    }

    public function getAccessor(): PropertyAccessorInterface
    {
        return $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
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

    public function getMapping(): null|AssociationMapping|FieldMapping
    {
        return $this->mapping;
    }

    public function getMappingName(): string
    {
        return $this->mapping_name;
    }

    public function isEmbedded(): bool
    {
        return count($this->parts) > 0;
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
    /** FIELDS                                                                                        */
    /**************************************************************************************************/

    public function isField(): bool
    {
        return $this->classMetadata?->hasField($this->mapping_name) ?? false;
    }

    public function isId(): bool
    {
        if(!$this->isField()) {
            return false;
        }
        return $this->classMetadata?->isIdentifier($this->mapping_name) ?? false;
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
        return $this->isRelation() 
            ? $this->between_map && is_a($this->mapping->targetEntity, BetweenManyInterface::class, true)
            : false;
    }

    public function getBetweenTarget(): false|WireClassMetadataInterface
    {
        return $this->betweenTarget;
    }

    // public function getTargetEntity(): false|WireClassMetadataInterface
    // {
    //     return $this->betweenTarget;
    // }

    public function getTargetNames(string $type = 'final'): false|array
    {
        if(isset($this->target_names)) {
            return $this->target_names;
        }
        if($this->isRelation()) {
            // Check by WireRelationMapping Attribute data if exists
            if($this->isBetweenRelation()) {
                $names = $this->wCmd->getWireClassMetadataManager()
                    ->setSearchMode($type)
                    ->setTypeCompareOr() // IMPORTANT!!!
                    ->filterClasses($this->between_map['require'])
                    ->mapSingleValue('shortname')
                    ;
                return $this->target_names = array_filter($names, fn ($class) => is_a($class, $this->betweenTarget->name, true), ARRAY_FILTER_USE_KEY);
            }
            return $this->target_names = $this->wCmd->getWireClassMetadataManager()
                ->setSearchMode($type)
                ->filterClasses([$this->mapping->targetEntity])
                ->mapSingleValue('shortname')
                ;
        }
        return $this->target_names = false;
    }

    public function isCascadePersist(): bool
    {
        return ($mapping = $this->getAssociationMapping()) ? in_array('persist', $mapping->cascade, true) : false;
    }


    /**************************************************************************************************/
    /** GETTER / SETTER                                                                               */
    /**************************************************************************************************/

    public function getValue(object $entity): mixed
    {
        return $this->getAccessor()->getValue($entity, $this->mapping_name);
    }

    public function setValue(object $entity, mixed $value): void
    {
        $this->getAccessor()->setValue($entity, $this->mapping_name, $value);
    }

}