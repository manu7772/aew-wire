<?php
namespace Aequation\WireBundle\Entity\interface;


interface TraitClonableInterface extends TraitInterface
{

    public function __construct_clonable(): void;
    public function with(...$values): static;

}