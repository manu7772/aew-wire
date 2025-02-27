<?php
namespace Aequation\WireBundle\Entity\interface;

use Serializable;

interface TraitSerializableInterface extends TraitInterface, Serializable
{

    // public function __construct_serializable(): void;
    public function __serialize(): array;
    public function __unserialize(array $data): void;

}