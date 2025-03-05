<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitUnamedInterface extends TraitEntityInterface, WireEntityInterface
{

    public function __construct_unamed(): void;
    public function updateUname(?string $uname = null): static;
    public function setUname(string $uname): static;
    public function getUname(): ?UnameInterface;
    public function getUnameName(): ?string;

}