<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitOwnerInterface extends TraitInterface
{

    public function __construct_owner(): void;
    public function getOwner(): ?WireUserInterface;
    public function setOwner(?WireUserInterface $owner): static;

}