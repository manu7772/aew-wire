<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusContainerInterface;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Component\interface\EntitySelfStateInterface;
use Aequation\WireBundle\Interface\ClassDescriptionInterface;
// PHP
use Stringable;

interface BaseEntityInterface extends Stringable, ClassDescriptionInterface
{
    // public const DO_EMBED_STATUS_EVENTS = [];
    // public function __toString(): string;
    public function __construct_entity(): void;
    public function getId();
    // Count updates
    public function doUpdate(): void;
    public function getUpdates(): int;
    // Embeded Status
    public function initializeSelfstate(): void;
    public function getSelfState(): ?EntitySelfStateInterface;
    public function hasEmbededStatus(): bool;
    public function getEmbededStatus(bool $load = true): null|EntityEmbededStatusContainerInterface|EntityEmbededStatusInterface|EntitySelfStateInterface;
    // App Wire Identifiers
    public function getEuid(): string;
    // public function setEuid(string $euid): static;
    public function getUnameThenEuid(): string;
    public function defineUname(string $uname): static;

}