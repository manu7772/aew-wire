<?php
namespace Aequation\WireBundle\Component\interface;

// Symfony
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\AssociationMapping;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;


interface WirePropertyAbstractMetadataInterface
{
    public function getName(): string;
    public function getMapping();
    // Virtuals
    public function isVirtualChild(): bool;
    public function hasVirtualChilds(): bool;
    public function getVirtualChilds(): array;
    // Magic calls
    public function __call($name, $arguments);
    public function __get($name);
    public function __isset($name);

}