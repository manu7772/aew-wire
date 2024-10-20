<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireWebsectionInterface extends WireHtmlcodeInterface
{
    public function getTwigfile(): ?string;
}