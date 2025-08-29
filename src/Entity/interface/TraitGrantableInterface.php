<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitGrantableInterface
{

    public function __construct_grantable(): void;
    public function getGrantlevel(): ?string;
    public function setGrantlevel(?string $grantlevel = null): static;

}