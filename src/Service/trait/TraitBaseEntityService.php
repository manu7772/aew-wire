<?php

namespace Aequation\WireBundle\Service\trait;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\Pagination\PaginationInterface;
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
        return $this->em ??= $this->wireEm->em;
    }

    public function getEm(): EntityManagerInterface
    {
        return $this->em ??= $this->wireEm->em;
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
        $entity = $this->wireEm->createEntity($this->getEntityClassname(), $data, $context, false); // false = do not try service IMPORTANT!!!
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
        $model = $this->wireEm->createModel($this->getEntityClassname(), $data, $context, false); // false = do not try service IMPORTANT!!!
        // Add some stuff here...
        return $model;
    }

    public function createClone(
        BaseEntityInterface $entity,
        array $changes = [], // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface|false
    {
        $clone = $this->wireEm->createClone($entity, $changes, $context, false); // false = do not try service IMPORTANT!!!
        // Add some stuff here...
        return $clone;
    }

    /**
     * Get entity classname
     *
     * @return string|null
     */
    public static function getEntityClassname(): string
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


    /************************************************************************************************************/
    /** QUERYS                                                                                                  */
    /************************************************************************************************************/

    public function getCount(
        bool|array $criteria = []
    ): int
    {
        if(is_bool($criteria)) {
            $criteria = $criteria ? static::getCriteriaEnabled() : static::getCriteriaDisabled();
        }
        return $this->getRepository()->count($criteria);
    }

    public function findAll(
        bool|array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array
    {
        if(is_bool($criteria)) {
            $criteria = $criteria ? static::getCriteriaEnabled() : static::getCriteriaDisabled();
        }
        $entities = $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
        return array_filter($entities, function ($entity) {
            if ($entity instanceof TraitEnabledInterface) {
                return $entity->isActive();
            }
            return true;
        });
    }

    public function find(
        int|string $identifier,
        bool|array $criteria = [],
        ?array $orderBy = null
    ): ?object
    {
        if(is_bool($criteria)) {
            $criteria = $criteria ? static::getCriteriaEnabled() : static::getCriteriaDisabled();
        }
        if(is_int($identifier) && $identifier > 0) {
            $criteria['id'] = $identifier;
        } else if(Encoders::isEuidFormatValid($identifier)) {
            $criteria['euid'] = $identifier;
        } elseif(Encoders::isUnameFormatValid($identifier)) {
            $euid = $this->wireEm->getEuidOfUname($identifier);
            if(empty($euid)) {
                throw new Exception(vsprintf('Error %s line %d: could not resolve euid with uname %s for class %s!', [__METHOD__, __LINE__, $identifier, static::getEntityClassname()]));
            }
            $criteria['euid'] = $euid;
        } else {
            throw new Exception(vsprintf('Error %s line %d: identifier "%s" is not valid!', [__METHOD__, __LINE__, $identifier]));
        }
        $entity = $this->getRepository()->findOneBy($criteria, $orderBy);
        if($entity instanceof TraitEnabledInterface) {
            return $entity->isActive() ? $entity : null;
        }
        return $entity;
    }


    /************************************************************************************************************/
    /** CRITERIA                                                                                                */
    /************************************************************************************************************/

    public static function getCriteriaEnabled(): array
    {
        return WireEntityManager::getCriteriaEnabled(static::getEntityClassname());
    }

    public static function getCriteriaDisabled(): array
    {
        return WireEntityManager::getCriteriaDisabled(static::getEntityClassname());
    }


}
