<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
// PHP
use Serializable;
use Stringable;

interface WireEntityInterface extends Stringable, Serializable
{

    public const IS_CLONABLE = false;

    // Embeded Status
    public function setEmbededStatus(EntityEmbededStatusInterface $estatus): void;
    public function getEmbededStatus(): EntityEmbededStatusInterface;
    // Interface of all entities
    public function getId(): ?int;
    public function getEuid(): ?string;
    public function getUnameThenEuid(): string;
    public function defineUname(string $uname): static;
    public function __toString(): string;
    public function __construct_entity(): void;
    // Classname
    public function getClassname(): string;
    // Shortname
    public function getShortname(bool $lowercase = false): string;
    // Serialization
    public function serialize(): ?string;
    public function unserialize(string $data): void;

}