<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireMenuInterface;

interface WireMenuServiceInterface extends WireEcollectionServiceInterface
{
    public function getMainMenu(): ?WireMenuInterface;
}