<?php
namespace Aequation\WireBundle\Entity\interface;

interface UnameInterface extends WireEntityInterface
{

    public static function isValidUname(string $uname): bool;
    public function attributeEntity(TraitUnamedInterface $entity, string $uname = null): static;
    public function setUname(string $uname): static;
    public function getUname(): string;
    public function getEntityEuid(): ?string;

}

