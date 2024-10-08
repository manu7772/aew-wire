<?php
namespace Aequation\WireBundle\Entity\interface;

interface TraitEnabledInterface extends TraitInterface
{
    public function __construct_enabled(): void;
    public function __clone_enabled(): void;
    public function isActive(): bool;
    public function isEnabled(): bool;
    public function isDisabled(): bool;
    public function setEnabled(bool $enabled): static;
    public function isSoftdeleted(): bool;
    public function setSoftdeleted(bool $softdeleted): static;
}