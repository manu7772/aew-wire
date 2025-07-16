<?php

namespace Aequation\WireBundle\Service\trait;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Dto\interfaace\WireEntityDtoInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\WireEntityManager;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ObjectMapper\Attribute\Map;
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
    /** GENERATION                                                                                      */
    /****************************************************************************************************/

    public function createEntity(
        array $data = [], // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface {
        $entity = $this->getWireEm()->getEntitiesMetadata()->newInstance($this->getEntityClassname(), $data, $context);
        if($this->getWireEm()->isGrantsCheckEnabled() && !$this->appWire->isGranted('new', $entity->getClassname())) {
            throw new Exception(vsprintf('Error %s line %d: you are not allowed to create %s%s!', [__METHOD__, __LINE__, $this->getEntityClassname(), $entity->getClassname() !== $this->getEntityClassname() ? ' (initially requested '.$this->getEntityClassname().')' : '']));
        }
        // Add some stuff here...
        return $entity;
    }

    /**
     * create model
     * 
     * @return BaseEntityInterface
     */
    public function createModel(
        array $data = [],
        array $context = []
    ): BaseEntityInterface
    {
        $model = $this->getWireEm()->getEntitiesMetadata()->newModel($this->getEntityClassname(), $data, $context);
        // Add some stuff here...
        return $model;
    }

    public function createClone(
        BaseEntityInterface $entity,
        array $changes = [], // ---> do not forget uname if wanted!
        array $context = []
    ): BaseEntityInterface|false
    {
        throw new Exception(vsprintf('Error %s line %d: method %s not implemented yet for %s.', [__METHOD__, __LINE__, __FUNCTION__, $this->getEntityClassname()]));
    }

    public function createDto(
        array $data = [],
        array $context = []
    ): ?WireEntityDtoInterface
    {
        $classes = $this->getDtoClassnames();
        $class = reset($classes);
        if(is_a($class, WireEntityDtoInterface::class, true)) {
            // Needs WireEntityManagerInterface to be passed
            return new $class($data, $this->getWireEm());
        }
        return $class ? new $class($data) : null;
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
        // throw new Exception(vsprintf('Method %s not implemented yet.', [__METHOD__]));
        $request ??= $this->appWire->getRequest();
        $fields =  [
            'id' => [
                'classes' => ['w-1'],
                'sortable' => true,
            ],
            // 'name' => [
            //     'view_options' => [
            //         'template' => ['from_string' => '{{ entity.name }}{% if entity.firstname is not null %}<span class="pl-2 italic text-sm font-extralight opacity-75"> {{ entity.firstname }}</span>{% endif %}']
            //     ],
            //     'sortable' => true,
            // ],
        ];
        $model = $this->createModel();
        $entities = $this->getPaginated();
        /** @var BaseWireRepository */
        $repo = $this->getRepository();
        return [
            'entities' => $entities,
            'fields' => $fields,
            'options' => [
                'alias' => $repo->getDefaultAlias(),
                'classname' => $model->getClassname(),
                'shortname' => $model->getShortname(),
                'trans_domain' => $model->getTrans_domain(),
                'actions' => true,
            ],
        ];
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
