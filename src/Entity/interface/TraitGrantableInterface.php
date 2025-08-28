<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitGrantableInterface
{

    public function __construct_grantable(): void;
    public function getGrant(): ?string;
    public function setGrant(?string $grant = null): static;

}