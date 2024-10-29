<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireWebpageInterface extends WireHtmlcodeInterface, TraitPreferedInterface
{
    public function getTwigfile(): ?string;
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
}