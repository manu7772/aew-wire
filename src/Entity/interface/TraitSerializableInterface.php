<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitSerializableInterface extends TraitInterface
{

    public function __construct_serializable(): void;
    public function __serialize(): array;
    public function __unserialize(array $data): void;

}