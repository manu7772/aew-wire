<?php

namespace Aequation\WireBundle\Entity\interface;

// Aequation

use Aequation\WireBundle\Component\EntitySelfState;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
// Symfony
// use Symfony\Component\Uid\UuidV7 as Uuid;
// PHP
use Stringable;

interface WireEntityInterface extends Stringable, TraitSerializableInterface
{
    // public function __toString(): string;
    public function __construct_entity(): void;
    // Entity self state
    public function doInitializeSelfState(string $state = 'auto', bool|string $debug = 'auto'): void;
    public function getSelfState(): ?EntitySelfState;
    // Embeded Status
    public function setEmbededStatus(EntityEmbededStatusInterface $estatus): void;
    public function hasEmbededStatus(): bool;
    public function getEmbededStatus(): ?EntityEmbededStatusInterface;
    // Interface of all entities
    public function getId(): mixed;
    public function getEuid(): ?string;
    public function getUnameThenEuid(): string;
    public function defineUname(string $uname): static;
    // Classname
    public function getClassname(): string;
    // Shortname
    public function getShortname(bool $lowercase = false): string;
    // Serialization
    public function serialize(): ?string;
    public function unserialize(string $data): void;
    public function __serialize(): array;
    public function __unserialize(array $data): void;
    // Icon
    public function getIcon(string $type = 'ux'): string;
}
