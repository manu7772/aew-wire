<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Component\interface\EntitySelfStateInterface;
// PHP
use Stringable;

interface BaseEntityInterface extends Stringable
{
    // Classname
    public function getClassname(): string;
    // Shortname
    public function getShortname(bool $lowercase = false): string;
    
    // public const DO_EMBED_STATUS_EVENTS = [];
    // public function __toString(): string;
    public function __construct_entity(): void;
    public function getId();
    // Count updates
    public function doUpdate(): void;
    public function getUpdates(): int;
    // Entity self state
    public function doInitializeSelfState(string $state = 'auto', bool|string $debug = 'auto'): void;
    public function getSelfState(): ?EntitySelfStateInterface;
    // Embeded Status
    public function setEmbededStatus(EntityEmbededStatusInterface $estatus): void;
    public function hasEmbededStatus(): bool;
    public function getEmbededStatus(): ?EntityEmbededStatusInterface;
    public function getEuid(): ?string;
    public function setEuid(string $euid): static;
    public function getUnameThenEuid(): string;
    public function defineUname(string $uname): static;

}