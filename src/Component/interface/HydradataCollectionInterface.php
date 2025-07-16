<?php
namespace Aequation\WireBundle\Component\interface;

interface HydradataCollectionInterface extends TypedCollectionInterface
{
    public function getByName(string $name): static;
}