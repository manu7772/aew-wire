<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireEcollection;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEcollectionServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(WireEcollectionServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class WireEcollectionService extends BaseService implements WireEcollectionServiceInterface
{
    public const ENTITY_CLASS = WireEcollection::class;

    protected BaseWireRepositoryInterface $repository;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService
    )
    {}

    public function getRepository(): BaseWireRepositoryInterface
    {
        return $this->repository ??= $this->wireEntityService->getRepository(static::ENTITY_CLASS);
    }

}