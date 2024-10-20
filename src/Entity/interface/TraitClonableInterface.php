<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitClonableInterface extends TraitInterface
{

    // public const IS_CLONABLE = false;

    public function __construct_clonable(): void;
    public function with(...$values): static;

}