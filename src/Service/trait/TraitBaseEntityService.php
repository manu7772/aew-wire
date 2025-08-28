<?php

namespace Aequation\WireBundle\Service\trait;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Component\PaginatedContextData;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
use Closure;
// Symfony
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Knp\Component\Pager\Pagination\PaginationInterface;
// PHP
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use ReflectionClassConstant;

trait TraitBaseEntityService
{

    // public const ENTITY_CLASS = BaseEntityInterface::class;

    protected readonly EntityManagerInterface $em;
    protected readonly UnitOfWork $uow;

    /****************************************************************************************************/
    /** SERVICES                                                                                        */
    /****************************************************************************************************/

    public function getWireEm(): WireEntityManagerInterface
    {
        return $this->wireEm;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em ??= $this->getWireEm()->getEm();
    }

    public function getWireClassMetadata(): ?WireClassMetadataInterface
    {
        return $this->getWireEm()->getEntitiesMetadata()->getWireClassMetadata(static::getEntityClassname());
    }

    public function getEm(): EntityManagerInterface
    {
        return $this->em ??= $this->getWireEm()->getEm();
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
    /** CREATE                                                                                          */
    /****************************************************************************************************/

    public function createEntity(array $data = [], array $options = []): object
    {
        return $this->getWireEm()->createEntity(static::getEntityClassname(), $data, $options);
    }

    public function createModel(array $data = [], array $options = []): BaseEntityInterface
    {
        return $this->getWireEm()->createModel(static::getEntityClassname(), $data, $options);
    }

    public function createClone(BaseEntityInterface $entity, array $changes = [], array $options = []): BaseEntityInterface|false
    {
        return $this->getWireEm()->createClone($entity, $changes, $options);
    }

    public function createDto(array $data = [], array $options = []): ?WireEntityDtoInterface
    {
        return $this->getWireEm()->createDto(static::getEntityClassname(), $data, $options);
    }


    /****************************************************************************************************/
    /** CHECK ACTIONS                                                                                   */
    /****************************************************************************************************/

    public function entityEventActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null): void
    {
        if(!is_a($entity, $this->getEntityClassname(), true)) {
            throw new Exception(vsprintf('Error %s line %d: entity %s is not a %s!', [__METHOD__, __LINE__, Objects::getClassname($entity), $this->getEntityClassname()]));
        }
        // ... Default event actions for entity
        if($entity->getSelfState()->isNew()) {
            // After created actions...
            $this->wireEm->defaultEntityEventActions($entity, $opresult);
        }
        if($entity->getSelfState()->isLoaded()) {
            // After loaded actions...
            $this->wireEm->defaultEntityEventActions($entity, $opresult);
        }
        // After all actions...
    }

    public function entityCheckActions(BaseEntityInterface $entity, ?OpresultInterface $opresult = null, bool $repair = false): void
    {
        $this->wireEm->defaultEntityCheckActions($entity, $opresult, $repair);
        if(is_a($entity, $this->getEntityClassname())) {
            if($entity->getSelfState()->isLoaded()) {
                // After loaded actions...
            } else {
                throw new Exception(vsprintf('Error %s line %d: check entity does not works with unloaded entities (got %s)!', [__METHOD__, __LINE__, Objects::toDebugString($entity)]));
            }
        } else {
            throw new Exception(vsprintf('Error %s line %d: entity %s is not a %s!', [__METHOD__, __LINE__, Objects::toDebugString($entity), $this->getEntityClassname()]));
        }
    }

    public function checkDatabase(OpresultInterface $opresult, bool $repair = false, array $options = []): void
    {
        $serviceOptions = (new ReflectionClassConstant(static::class, 'DEFAULT_CHECK_DB_OPTIONS'))->getValue();
        $this->paginatedAction(
            callback: function ($entity) use ($opresult, $repair) {
                $this->entityCheckActions($entity, $opresult);
                return $repair;
            },
            options: array_merge($serviceOptions, $options)
        );
    }

    /****************************************************************************************************/
    /** INFORMATIONS                                                                                    */
    /****************************************************************************************************/

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

    public function getEntityShortname(): string
    {
        $classname = static::getEntityClassname();
        if (empty($classname)) {
            throw new Exception(vsprintf('Error %s line %d: entity shortname not defined for class %s!', [__METHOD__, __LINE__, static::class]));
        }
        return Objects::getShortname($classname);
    }

    public function getDtoClassnames(): array
    {
        $rconstant = new ReflectionClassConstant(static::class, 'ENTITY_CLASS');
        if(!is_a($rconstant->getValue(), WireHydratable::class, true)) {
            throw new Exception(vsprintf('Error %s line %d: constant ENTITY_CLASS must be an instance of %s!', [__METHOD__, __LINE__, WireHydratable::class]));
        }
        return array_map(
            fn (Map $map): string => $map->source,
            $this->getWireEm()->getEntitiesMetadata()->getDtoSourceMaps($rconstant->getValue())
        );
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
    /** @see https://grafikart.fr/tutoriels/symfony-pagination-2191                                     */
    /****************************************************************************************************/

    /**
     * Paginated action
     * - Use this method to perform actions on paginated entities
     * - The callback should return true if the entity has been modified and needs to be flushed
     *
     * - Example of usage:
     *  
     *   $options = [
     *      'flush_one_by_one' => false,
     *      'load_all_if_less_or_equal_than' => 1000,
     *   ];
     *   $this->paginatedAction(
     *       function ($entity) {
     *           $entity->doUpdate();
     *           return true;
     *       },
     *       $method,
     *       $parameters,
     *       $options
     *   );
     *
     * @param Closure $callback
     * @param string|null $method
     * @param array $parameters
     * @param array $options
     */
    public function paginatedAction(Closure $callback, ?string $method = null, array $parameters = [], array $options = []): void
    {
        $this->getEm();
        $default_options = [
            'number_per_page' => 10,
            'load_all_if_less_or_equal_than' => 0, // If the number of entities is less or equal than this value, all entities will be loaded in one query
            'flush_one_by_one' => true,
            'paginator.distinct.enable' => false,
            'fetch_join_collection' => false, // Set to true if you want to fetch the collection in one query
        ];
        $options = array_merge($default_options, $options);
        $query = !empty($method) ? $this->getRepository()->$method(...$parameters) : $this->getRepository()->createQueryBuilder('r')->getQuery();
        foreach ($options as $name => $value) {
            switch (true) {
                case $name === Paginator::HINT_ENABLE_DISTINCT:
                    $query->setHint(Paginator::HINT_ENABLE_DISTINCT, $value);
                    break;
            }
        }
        $paginator = new Paginator($query, $options['fetch_join_collection']);
        $nbpp = !empty($options['load_all_if_less_or_equal_than']) && $paginator->count() <= $options['load_all_if_less_or_equal_than'] ? $options['load_all_if_less_or_equal_than'] : $options['number_per_page'];
        $page = 1;
        $query->setMaxResults($nbpp);
        $query->setFirstResult(($page - 1) * $nbpp);
        $nbpages = ceil($paginator->count() / $nbpp);
        // dump($paginator, $paginator->count());
        while ($page <= $nbpages) {
            // dump('*************** Page '.$page.'/'.$nbpages.' > '.count($paginator->getQuery()->getResult()).' result(s) ***************');
            $flush = false;
            $unsets = [];
            foreach ($paginator as $entity) {
                $doflush = $callback($entity);
                if($doflush && $options['flush_one_by_one']) {
                    // dump('*** FLUSHING (ONE BY ONE) ***');
                    $this->em->flush();
                    $this->em->clear();
                    unset($entity);
                } else {
                    $flush |= $doflush;
                    $unsets[spl_object_hash($entity)] = $entity;
                }
            }
            if($flush) {
                // dump('*** FLUSHING (PAGE '.$page.') ***');
                $this->em->flush();
                $this->em->clear();
            }
            foreach ($unsets as $unset) {
                unset($unset);
            }
            $page++;
            $query->setFirstResult(($page - 1) * $nbpp);
        }
    }

    /**
     * Get paginated entities.
     *
     * @param integer|null $page
     * @param string|null $method
     * @param array $parameters
     * @return PaginationInterface
     */
    public function getPaginated(?int $page = null, ?string $method = null, array $parameters = []): PaginationInterface
    {
        if (empty($page)) $page = $this->appWire->getRequest()?->query->getInt('page', 1) ?: 1;
        $method ??= 'findPaginated';
        $query = $this->getRepository()->$method(...$parameters);
        $query->setHint(Paginator::HINT_ENABLE_DISTINCT, false);
        return $this->paginator->paginate($query, $page);
    }

    /**
     * Get paginated context data.
     *
     * @param Request $request
     * @return array
     */
    public function getPaginatedContextData(array $options = []): PaginatedContextDataInterface
    {
        return new PaginatedContextData($this, $options);
    }


    /************************************************************************************************************/
    /** QUERYS                                                                                                  */
    /************************************************************************************************************/

    public function count(
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

    public function findOneBy(
        null|int|string $identifier = null,
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
            $euid = $this->getWireEm()->getEuidOfUname($identifier);
            if(empty($euid)) {
                throw new Exception(vsprintf('Error %s line %d: could not resolve euid with uname %s for class %s!', [__METHOD__, __LINE__, $identifier, static::getEntityClassname()]));
            }
            $criteria['euid'] = $euid;
        } else if($identifier !== null) {
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
