<?php
namespace Aequation\WireBundle\Entity\interface;


interface BetweenManyInterface
{
    public function __construct(
        BetweenManyParentInterface $parent,
        BetweenManyChildInterface $child
    );
    public function getParent(): BetweenManyParentInterface;
    public function getChild(): BetweenManyChildInterface;
}