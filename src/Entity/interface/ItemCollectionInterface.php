<?php
namespace Aequation\WireBundle\Entity\interface;


interface ItemCollectionInterface
{
 
    public function getEcollection(): WireEcollectionInterface;
    public function getItem(): WireItemInterface;
    public function getPosition(): int;
    public function setPosition(int $position): static;

}