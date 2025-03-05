<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitOwnerInterface extends TraitEntityInterface
{

    public function __construct_owner(): void;
    public function isOwnerRequired(): bool;
    public function getOwner(): ?WireUserInterface;
    public function setOwner(?WireUserInterface $owner): static;

}