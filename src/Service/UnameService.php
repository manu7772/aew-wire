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
class UnameService extends BaseService implements UnameServiceInterface
{
    public const ENTITY_CLASS = Uname::class;

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