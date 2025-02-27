<?php
namespace Aequation\WireBundle\Entity\interface;

use Gedmo\Sortable\Sortable;

interface WireRelinkInterface extends WireEntityInterface, TraitDatetimedInterface, TraitSlugInterface, TraitUnamedInterface, Sortable
{

    public function getItemowner(): WireItemInterface & TraitRelinkableInterface;
    public function setItemowner(WireItemInterface & TraitRelinkableInterface $itemowner): static;

}