<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireItemCollectionInterface extends BetweenSortedInterface
{
    public function getParent(): WireEcollectionInterface;
    public function getChild(): WireItemInterface;
    public function preRemove(): static;
}