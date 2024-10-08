<?php
namespace Aequation\WireBundle\Attribute\interface;

interface AppAttributeInterface
{

    public function getClassObject(): ?object;
    public function setClass(object $class): static;

}