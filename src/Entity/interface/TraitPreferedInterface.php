<?php
namespace Aequation\WireBundle\Entity\interface;

interface TraitPreferedInterface extends BaseEntityInterface
{
    public function __construct_prefered(): void;
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
}