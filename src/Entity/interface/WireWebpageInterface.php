<?php
namespace Aequation\WireBundle\Entity\interface;


interface WireWebpageInterface extends WireHtmlcodeInterface
{
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
}