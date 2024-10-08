<?php
namespace Aequation\WireBundle\Attribute\interface;

// PHP
use ReflectionClassConstant;

interface AppAttributeConstantInterface extends AppAttributeInterface
{

    public function setConstant(ReflectionClassConstant $constant): static;
    public function getValue(): mixed;

}
