<?php
namespace Aequation\WireBundle\Entity\interface;

use Gedmo\Sluggable\Sluggable;

interface SluggableInterface extends Sluggable, TranslationEntityInterface, TraitEntityInterface
{

    public function getSlug(): ?string;
    // public function setSlug(string $slug): static;

}