<?php
namespace Aequation\WireBundle\Entity\interface;

interface TraitEnabledInterface extends BaseEntityInterface
{
    public function __construct_enabled(): void;
    public function isActive(): bool;
    public function isEnabled(): bool;
    public function isDisabled(): bool;
    public function setEnabled(bool $enabled): static;
}