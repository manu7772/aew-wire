<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitScreenableInterface extends TraitInterface
{

    public function __construct_screenable(): void;
    public function setWebpage(WireHtmlcodeInterface $pageweb): static;
    public function getWebpage(): ?WireHtmlcodeInterface;

}