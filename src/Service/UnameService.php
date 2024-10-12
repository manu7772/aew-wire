<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(UnameServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class UnameService extends BaseWireEntityService implements UnameServiceInterface
{
    public const ENTITY_CLASS = Uname::class;

}