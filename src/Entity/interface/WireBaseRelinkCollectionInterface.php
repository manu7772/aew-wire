<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireBaseRelinkCollectionInterface extends BetweenSortedInterface
{
    public function getParent(): TraitRelinkableInterface&BetweenSortedParentInterface;
    public function getRelink(): WireRelinkInterface&BetweenSortedChildInterface;
}