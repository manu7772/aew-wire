<?php

namespace Aequation\WireBundle\Service\trait;

use Aequation\WireBundle\Component\NormalizeDataContainer;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Serializer\EntityDenormalizer;
use Aequation\WireBundle\Service\NormalizerService;
use Aequation\WireBundle\Service\WireEntityManager;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\Request;
// PHP
use Exception;
use ReflectionClassConstant;

trait TraitBaseEntityService
{

    // public const ENTITY_CLASS = BaseEntityInterface::class;

    protected readonly EntityManagerInterface $em;
    protected readonly UnitOfWork $uow;

    /****************************************************************************************************/
    /** SERVICES                                                                                        */
    /****************************************************************************************************/

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em ??= $this->wireEntityService->em;
    }

    public function getEm(): EntityManagerInterface
    {
        return $this->getEntityManager();
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->uow ??= $this->getEm()->getUnitOfWork();
    }

    public function getUow(): UnitOfWork
    {
        return $this->getUnitOfWork();
    }


    /****************************************************************************************************/
    /** GENERATION                                                                                      */
    /****************************************************************************************************/

    public function createEntity(
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface {
        $entity = $this->wireEntityService->createEntity($this->getEntityClassname(), $data, $context, false); // false = do not try service IMPORTANT!!!
        // Add some stuff here...
        return $entity;
    }

    /**
     * create model
     * 
     * @return BaseEntityInterface
     */
    public function createModel(
        array|false $data = false,
        array $context = []
    ): BaseEntityInterface
    {
        $model = $this->wireEntityService->createModel($this->getEntityClassname(), $data, $context, false); // false = do not try service IMPORTANT!!!
        // Add some stuff here...
        return $model;
    }

    public function createClone(
        BaseEntityInterface $entity,
        array $changes = [], // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface|false
    {
        $clone = $this->wireEntityService->createClone($entity, $changes, $context, false); // false = do not try service IMPORTANT!!!
        // Add some stuff here...
        return $clone;
    }

    /**
     * Get entity classname
     *
     * @return string|null
     */
    public function getEntityClassname(): string
    {
        $rconstant = new ReflectionClassConstant(static::class, 'ENTITY_CLASS');
        return $rconstant->getValue();
    }

    /**
     * Get Repository
     *
     * @return EntityRepository
     */
    public function getRepository(
        ?string $classname = null
    ): ?EntityRepository {
        $classname ??= $this->getEntityClassname();
        return $this->getEm()?->getRepository($classname) ?: null;
    }

    /**
     * Get COUNT of entities (with optional criteria)
     *
     * @param array $criteria
     * @return integer
     */
    public function getEntitiesCount(
        array $criteria = [],
        ?string $classname = null
    ): int|false {
        /** @var EntityRepository */
        $repository = $this->getRepository($classname);
        return $repository ? $repository->count($criteria) : false;
    }


    /****************************************************************************************************/
    /** PAGINABLE                                                                                       */
    /****************************************************************************************************/

    /**
     * Get paginated entities.
     *
     * @param integer|null $page
     * @param string|null $method
     * @param array $parameters
     * @return PaginationInterface
     */
    public function getPaginated(
        ?int $page = null,
        ?string $method = null,
        array $parameters = []
    ): PaginationInterface {
        if (empty($page)) $page = $this->appWire->getRequest()->query->getInt('page', 1);
        if (empty($method)) $method = 'findPaginated';
        $query = $this->getRepository()->$method(...$parameters);
        return $this->paginator->paginate($query, $page);
    }

    /**
     * Get paginated context data.
     *
     * @param Request $request
     * @return array
     */
    public function getPaginatedContextData(
        ?Request $request = null
    ): array {
        // $request ??= $this->appWire->getRequest();
        throw new Exception(vsprintf('Method %s not implemented yet.', [__METHOD__]));
    }
}
