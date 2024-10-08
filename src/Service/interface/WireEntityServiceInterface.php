<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;

interface WireEntityServiceInterface extends WireServiceInterface
{
    public function getRepository(): BaseWireRepositoryInterface;
    
}