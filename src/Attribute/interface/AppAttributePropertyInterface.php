<?php
namespace Aequation\WireBundle\Attribute\interface;

// PHP

use Aequation\WireBundle\Event\WireEntityEvent;
use ReflectionProperty;

interface AppAttributePropertyInterface extends AppAttributeInterface
{

    public function setProperty(ReflectionProperty $property): static;
    public function getProperty(): ReflectionProperty;

}