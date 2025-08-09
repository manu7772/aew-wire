<?php
namespace Aequation\WireBundle\Entity\interface;

interface TraitPreferedInterface extends BaseEntityInterface
{
    // public const MAX_PREFERED = null;
    // public const MIN_PREFERED = 1;
    // public const BY_PREFERED = [];

    public function __construct_prefered(): void;
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
    public function getMaxPrefered(): ?int;
    public function getMinPrefered(): ?int;
    public function getPreferedBy(): array;
}