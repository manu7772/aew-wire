<?php
namespace Aequation\WireBundle\Entity\interface;

interface TraitMainimageInterface
{

    public function getMainimage(): ?WireImageInterface;
    public function setMainimage(?WireImageInterface $mainimage): static;

}