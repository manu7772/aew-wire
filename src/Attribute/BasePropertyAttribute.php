<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Attribute\interface\AppAttributePropertyInterface;
use Aequation\WireBundle\Event\WireEntityEvent;
// PHP
use ReflectionProperty;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class BasePropertyAttribute extends BaseClassAttribute implements AppAttributePropertyInterface
{

    public readonly ReflectionProperty $property;
    public readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
    }

    public function setProperty(ReflectionProperty $property): static
    {
        $this->property = $property;
        return $this;
    }

    public function getProperty(): ReflectionProperty
    {
        return $this->property;
    }

    public function getvalue(): mixed
    {
        return $this->propertyAccessor->getValue($this->object, $this->property->name);
    }

    public function setValue(
        mixed $value
    ): void
    {
        $this->propertyAccessor->setValue($this->object, $this->property->name, $value);
    }


    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $parent['property'] = $this->property->name;
        return $parent;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->setProperty($this->class->getProperty($data['property']));
    }

}