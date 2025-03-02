<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireWebsectionInterface extends WireItemInterface
{
    public function getTwigfile(): ?string;
}