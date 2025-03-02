<?php
namespace Aequation\WireBundle\Entity\interface;

use Gedmo\Sortable\Sortable;

interface WireRelinkInterface extends WireEntityInterface, TraitDatetimedInterface, TraitUnamedInterface, Sortable, TranslationEntityInterface
{

    public function getItemowner(): WireItemInterface & TraitRelinkableInterface;
    public function setItemowner(WireItemInterface & TraitRelinkableInterface $itemowner): static;

}