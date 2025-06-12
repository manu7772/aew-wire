<?php
namespace Aequation\WireBundle\Attribute\interface;

// PHP
use ReflectionProperty;

interface AppAttributePropertyInterface extends AppAttributeInterface
{

    public function setProperty(ReflectionProperty $property): static;
    public function getProperty(): ReflectionProperty;
    public function getPropertyName(): ?string;

}