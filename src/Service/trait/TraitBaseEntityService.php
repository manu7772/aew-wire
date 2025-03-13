<?php

namespace Aequation\WireBundle\Service\trait;

use Aequation\WireBundle\Component\NormalizeOptionsContainer;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
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

    // public const ENTITY_CLASS = WireEntityInterface::class;

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

    protected function createNewEntity(
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface {
        $classname = $this->getEntityClassname();
        $normalizeContainer = new NormalizeOptionsContainer(context: $context);
        $entity = $this->normalizer->denormalizeEntity($data, $classname, null, $normalizeContainer->getContext());
        return $entity;
    }

    public function createEntity(
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface {
        $normalizeContainer = new NormalizeOptionsContainer(true, false, $context);
        $entity = $this->createNewEntity($data, $normalizeContainer->getContext());
        // Add some stuff here...
        return $entity;
    }

    /**
     * create model
     * 
     * @return WireEntityInterface
     */
    public function createModel(
        ?array $data = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface {
        if (empty($context['groups'] ?? null)) {
            $context['groups'] = NormalizerService::getDenormalizeGroups($this->getEntityClassname(), type: 'model');
        }
        $model = $this->createNewEntity($data, $context);
        // Add some stuff here...
        return $model;
    }

    public function createClone(
        WireEntityInterface $entity,
        ?array $changes = [], // ---> do not forget uname if wanted!
        ?array $context = []
    ): WireEntityInterface|false {
        if (empty($context['groups'] ?? null)) {
            $context['groups'] = NormalizerService::getNormalizeGroups($entity, type: 'clone');
        }
        $data = $this->normalizer->normalizeEntity($entity, null, $context);
        $context['groups'] = NormalizerService::getDenormalizeGroups($entity, type: 'clone');
        $clone = $this->createEntity(array_merge($data, $changes));
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
