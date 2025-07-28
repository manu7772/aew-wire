<?php
namespace Aequation\WireBundle\Entity\interface;


interface BetweenManyInterface
{
    public function getParent(): object;
    public function getChild(): object;
}