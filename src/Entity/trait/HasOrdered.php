<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Attribute\OnEventCall;
use Aequation\WireBundle\Attribute\RelationOrder;
use Aequation\WireBundle\Entity\interface\TraitHasOrderedInterface;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use ReflectionProperty;
use Exception;

trait HasOrdered
{

    public const KEEP_ORDERED_INDEXES = false;

    public function __construct_hasOrdered(): void
    {
        if(!($this instanceof TraitHasOrderedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitHasOrderedInterface::class]));
    }

    #[ORM\Column]
    #[Serializer\Ignore]
    protected ?array $relationOrder = [];

    #[OnEventCall(events: [WireEntityEvent::FORM_POST_SUBMIT, WireEntityEvent::BEFORE_PERSIST, WireEntityEvent::BEFORE_UPDATE])]
    public function updateRelationOrder(
        WireEntityEvent $event
    ): bool
    {
        $attributes = Objects::getPropertyAttributes($this, RelationOrder::class, true);
        if(empty($attributes)) throw new Exception(vsprintf('Error %s line %d: no field found for %s in entity %s!', [__METHOD__, __LINE__, RelationOrder::class, $this->getClassname()]));
        $old = $this->getRelationOrder();
        ksort($old);
        // dump($old);
        $old = json_encode($old);
        $new = [];
        foreach ($attributes as $properties) {
            foreach ($properties as $attribute) {
                $property = $attribute->property->name;
                if(isset($new[$property])) throw new Exception(vsprintf('Error %s line %d: property "%s" already defined for %s attribute!', [__METHOD__, __LINE__, $property, RelationOrder::class]));
                $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
                $property_elements = $propertyAccessor->getValue($this, $property);
                $new[$property] = [];
                foreach($property_elements as $rel) {
                    /** @var AppEntityInterface $rel */
                    $new[$property][] = $rel->getEuid();
                }
            }
        }
        ksort($new);
        // dd($new);
        if($old !== json_encode($new)) {
            $this->relationOrder = $new;
            // $this->_appManaged->setRelationOrderLoaded(false);
            $this->loadedRelationOrder(force: true);
            // dd($this->getItems());
            return true;
        }
        // dd($this->getItems());
        return false;
    }

    #[OnEventCall(events: [WireEntityEvent::ON_LOAD])]
    public function RelationOrderOnLoad(
        WireEntityEvent $event
    ): static
    {
        return $this->loadedRelationOrder($event);
    }

    public function loadedRelationOrder(
        WireEntityEvent $event = null,
        array $params = [],
        bool $force = false
    ): static
    {
        // dd('sort ordered relations...', $manager);
        // $manager ??= $this->_appManaged;
        // dump(array_merge($this->getRelationOrder(), ['entity' => [$this->shortname, $this->name]]));
        if($force || !$this->_appManaged->isRelationOrderLoaded(false)) {
            $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
            foreach($this->getRelationOrder() as $property => $values) {
                $property_elements = $propertyAccessor->getValue($this, $property);
                $collection = new ArrayCollection();
                try {
                    foreach ($property_elements as $item) {
                        $collection->set($item->getEuid(), $item);
                        $property_elements->removeElement($item);
                        $rest = clone $property_elements;
                        $property_elements->clear();
                    }
                    foreach ($values as $euid) {
                        if($item = $collection->get($euid)) {
                            if(static::KEEP_ORDERED_INDEXES) {
                                $property_elements->set($euid, $item);
                            } else if(!$property_elements->contains($item)) {
                                $property_elements->add($item);
                            }
                        }
                    }
                    if(!$rest->isEmpty()) {
                        foreach ($rest as $item) {
                            if(static::KEEP_ORDERED_INDEXES) {
                                $property_elements->set($item->getEuid(), $item);
                            } else if(!$property_elements->contains($item)) {
                                $property_elements->add($item);
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    // dd($this, $property_elements, $th);
                }
            }
            $this->_appManaged->setRelationOrderLoaded(true);
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function getRelationOrderDetails(): string
    {
        return json_encode($this->relationOrder);
    }

    #[Serializer\Ignore]
    public function getRelationOrderNames(
        string|ReflectionProperty|null $property = null
    ): array
    {
        if($property instanceof ReflectionProperty) $property = $property->name;
        $names = [];
        foreach (array_keys($this->relationOrder) as $prop) {
            foreach ($this->$prop as $item) {
                if(empty($property)) {
                    $names[] = '['.$prop.']'.$item->__toString();
                } else if($prop === $property) {
                    $names[] = $item->__toString();
                }
            }
        }
        return $names;
    }

    #[Serializer\Ignore]
    public function getRelationOrder(): array
    {
        return $this->relationOrder ??= [];
    }

    #[Serializer\Ignore]
    public function getPropRelationOrder(
        string|ReflectionProperty $property
    ): array
    {
        if($property instanceof ReflectionProperty) $property = $property->name;
        return $this->relationOrder[$property];
    }

}
