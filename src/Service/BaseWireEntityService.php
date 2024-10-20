<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;

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
        $classname = $this->getEntityClassname();
        if(empty($classname)) {
            throw new Exception(vsprintf('Error %s line %d: classname is missing! (Tryed with static::ENTITY_CLASS: %s).', [__METHOD__, __LINE__, json_encode(static::ENTITY_CLASS)]));
        }
        return $this->wireEntityService->getRepository($classname);
    }

    public function getEntitiesCount(array $criteria = []): int
    {
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return $repository->count($criteria);
    }

}
