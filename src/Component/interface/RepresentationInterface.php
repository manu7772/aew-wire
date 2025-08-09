<?php
namespace Aequation\WireBundle\Component\interface;


interface RepresentationInterface
{
    public function __toRepresentation(): string;
}
