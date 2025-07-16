<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Component\WireClassMetadata;
use Aequation\WireBundle\Dto\interfaace\WireEntityDtoInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Tools\Objects;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
// Symfony
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
// PHP
use Exception;

class BaseDto implements WireEntityDtoInterface, WireHydratable
{

    public readonly string $_target;
    public readonly WireEntityServiceInterface $_service;
    public readonly WireClassMetadataManagerInterface $_wCmdm;
    public readonly WireClassMetadataInterface $_wCmd;
    public bool $_createRelations = true;
    public PropertyAccessorInterface $_accessor;

    public function __construct(
        array $data,
        public WireEntityManagerInterface $_wireEm
    ) {
        $this->_accessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
        $this->_wCmdm = $this->_wireEm->getEntitiesMetadata();
        $maps = Objects::getDtoTargetClassnames(
            static::class,
            fn (Map $map): bool => is_a($map->target, WireHydratable::class, true)
        );
        $this->_target = reset($maps);
        $this->_service = $this->_wireEm->getEntityService($this->_target);
        $this->_wCmd = $this->_service->getWireClassMetadata();
        if($this->_wCmd->name !== $this->_target) {
            throw new Exception(sprintf('Error %s line %d: Dto target "%s" does not match service metadata name "%s"!', __METHOD__, __LINE__, $this->_target, $this->_wCmd->name));
        }
        $this->integrateData($data);
    }

    public function __toString(): string
    {
        return static::class;
    }

    public function toArray(): array
    {
        $values = array_filter(
            get_object_vars($this),
            fn (mixed $value, string $name): bool => preg_match('/^(?!_)/', $name) && (!empty($value) || is_numeric($value) || is_bool($value)),
            ARRAY_FILTER_USE_BOTH
        );
        return array_map(
            function (mixed $value): null|string|int|float|bool|array {
                switch (true) {
                    case $value instanceof DateTimeInterface:
                        return $value->format(DATE_ATOM);
                        break;
                    default:
                        return $value;
                        break;
                };
            },
            $values
        );
    }

    public function createRelations(bool $_createRelations): static
    {
        $this->_createRelations = $_createRelations;
        return $this;
    }

    public function isCreateRelations(): bool
    {
        return $this->_createRelations;
    }

    public function integrateData(array $data): void
    {
        foreach ($data as $attr => $value) {
            if(!is_null($value)) {
                if(preg_match('/^(?!_)/', $attr) && property_exists($this, $attr)) {
                    if(array_key_exists($attr, $this->_wCmd->fieldMappings)) {
                        $type = $this->_wCmd->fieldMappings[$attr]['type'];
                        switch ($type) {
                            case 'datetime':
                            case 'datetime_immutable':
                                $this->{$attr} = new DateTimeImmutable($value);
                                break;
                            // case 'json':
                            //     $this->{$attr} = $value;
                            //     break;
                            default:
                                $this->{$attr} = $value;
                                break;
                        }
                    } else if(array_key_exists($attr, $this->_wCmd->associationMappings)) {
                        /** @var AssociationMapping */
                        $relation = $this->_wCmd->associationMappings[$attr];
                        switch (true) {
                            case $attr === 'uname':
                                $this->{$attr} = $value;
                                break;
                            case $relation->isToOne():
                                if($related = $this->_wireEm->findEntityByUname($value)) {
                                    $this->{$attr} = $related;
                                }
                                if(!is_object($this->{$attr})) {
                                    $this->{$attr} = null;
                                }
                                break;
                            case $relation->isToMany():
                                $collection = new ArrayCollection();
                                foreach ($value as $val) {
                                    if($related = $this->_wireEm->findEntityByUname($val)) {
                                        if(!$collection->contains($related)) {
                                            $collection->add($related);
                                        }
                                    } else {
                                        throw new Exception(vsprintf('Error %s line %d: could not find related entity "%s" for attribute "%s" in class "%s".', [__METHOD__, __LINE__, $val, $attr, static::class]));
                                    }
                                }
                                // Do not replace the collection if it's empty
                                if(!$collection->isEmpty()) {
                                    $this->{$attr} = $collection;
                                }
                                if(!($this->{$attr} instanceof ArrayCollection)) {
                                    $this->{$attr} = new ArrayCollection();
                                }
                                break;
                            default:
                                throw new Exception(vsprintf('Error %s line %d: unknown relation type for attribute "%s" in class "%s".', [__METHOD__, __LINE__, $attr, static::class]));
                                break;
                        }
                    } else {
                        // Try call
                        $this->_accessor->setValue(
                            $this,
                            $attr,
                            $value
                        );
                    }
                }
            }
        }
        // dd($this, $this->toArray());
    }

}