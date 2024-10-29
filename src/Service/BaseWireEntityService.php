<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
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


    public function createEntity(string $uname = null): WireEntityInterface
    {
        $classname = $this->getEntityClassname();
        $new = $this->wireEntityService->__innerCreateEntity($classname, $uname);
        return $new;
    }

    public function createModel(): WireEntityInterface
    {
        $classname = $this->getEntityClassname();
        $model = $this->wireEntityService->__innerCreateModel($classname);
        return $model;
    }

    public function createClone(WireEntityInterface $entity, string $uname = null, int $clone_method = 1): ?WireEntityInterface
    {
        if($this->getEntityClassname() === $entity->getClassname()) {
            return $this->wireEntityService->__innerCreateClone($entity, $uname, $clone_method);
        }
        return null;
    }


    /**
     * Get entity classname
     *
     * @return string|null
     */
    public function getEntityClassname(): ?string
    {
        return static::ENTITY_CLASS;
    }

    /**
     * Get Repository
     *
     * @return BaseWireRepositoryInterface
     */
    public function getRepository(): BaseWireRepositoryInterface
    {
        $classname = $this->getEntityClassname();
        if(empty($classname)) {
            throw new Exception(vsprintf('Error %s line %d: classname is missing! (Tryed with static::ENTITY_CLASS: %s).', [__METHOD__, __LINE__, json_encode(static::ENTITY_CLASS)]));
        }
        return $this->wireEntityService->getRepository($classname);
    }

    /**
     * Get COUNT of entities (with optional criteria)
     *
     * @param array $criteria
     * @return integer
     */
    public function getEntitiesCount(array $criteria = []): int
    {
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return $repository->count($criteria);
    }

    public function persist(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $this->wireEntityService->__innerPersist($entity, $flush);
        return $this;
    }

    public function update(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $this->wireEntityService->__innerUpdate($entity, $flush);
        return $this;
    }

    public function remove(
        WireEntityInterface $entity,
        bool $flush = false
    ): static
    {
        $this->wireEntityService->__innerRemove($entity, $flush);
        return $this;
    }

    /**
     * Flush
     *
     * @return static
     */
    public function flush(): static
    {
        $this->wireEntityService->flush();
        return $this;
    }

}
