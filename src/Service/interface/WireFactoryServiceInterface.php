<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireFactoryInterface;

interface WireFactoryServiceInterface extends WireItemServiceInterface
{
    public function getPreferedFactory(): ?WireFactoryInterface;
}