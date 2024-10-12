<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

// #[AsAlias(WireUserServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class BaseWireEntityService extends BaseService implements WireEntityServiceInterface
{

    public const ENTITY_CLASS = null;

    protected Security $security;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService
    )
    {}

    public function getEntityClassname(): ?string
    {
        return static::ENTITY_CLASS;
    }

    public function getRepository(): BaseWireRepositoryInterface
    {
        return $this->wireEntityService->getRepository($this->getEntityClassname());
    }

    public function getEntitiesCount(array $criteria = []): int
    {
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return $repository->count($criteria);
    }

}
